import {Controller} from '@hotwired/stimulus';
import {Loading, Notify} from "notiflix";
import {ValidationError} from "../common/ValidationError";
import hyperform from "hyperform";

export default class extends Controller {

    dialog;
    formValidator;

    static targets = ['dialogHeader', 'dialogBody', 'dialogFooter', 'dialogTitle', 'form']
    static values = {
        loadUrl: String,
        id: String,
        genericErrorMsg: String,
        preSubmitEvent: String,
    }

    connect() {
        this.dialog = new bootstrap.Modal(this.element, {
            backdrop: 'static'
        })

    }

    requested(event) {
        if (this.idValue !== event.detail.dialogId) {
            return
        }
        this.loadDialog(event.detail.loadUrl)
    }

    formTargetConnected(element) {
        this.formValidator = hyperform(element, {
            classes: {
                invalid: 'is-invalid',
                warning: 'text-danger',
            }
        })
    }

    async loadDialog(loadUrl) {
        Loading.pulse()
        const response = await fetch(loadUrl)
        if (!response.ok) {
            Loading.remove()
            Notify.failure(this.genericErrorMsgValue)
            return
        }
        const json = await response.json()
        this.dialogBodyTarget.innerHTML = json.view
        if (json.title) {
            this.dialogTitleTarget.innerHTML = json.title
        }
        Loading.remove()
        this.dialog.show()
    }

    async submit(event) {
        if (this.formTarget.length === 0) {
            return
        }
        const validationState = {valid: true}
        await this.dispatch("before_submit", {
            target: this.formTarget,
            detail: validationState
        })
        event.preventDefault()
        if (!validationState.valid || !this.formTarget.reportValidity()) {
            return
        }
        Loading.standard()
        const headers = new Headers()
        headers.append("Accept", "application/json")
        try {
            const response = await fetch(this.formTarget.getAttribute('action'), {
                method: 'POST',
                body: new FormData(this.formTarget),
                headers: headers
            })
            if (!response.ok) {
                const errorResponse = response.status === 400 ? await response.json() : null
                let message = errorResponse.msg !== undefined ? errorResponse.msg : this.genericErrorMsgValue;
                throw new ValidationError(message, null, errorResponse.form);
            }
            const json = await response.json()
            if (json.msg !== undefined){
                Notify.success(json.msg)
            }
            if (json.triggerEvent !== undefined && json.triggerEvent === true){
                this.dispatch('dialog_processed', {
                    detail: {
                        dialogId: this.idValue
                    }
                });
                this.dialog.hide();
                return;
            }
            Turbo.visit(window.location.href, {action: "replace"});
            this.dialog.hide();
        } catch (error) {
            if (error instanceof ValidationError) {
                Notify.warning(error.message)
                this.dialogBodyTarget.innerHTML = error.parameters
                return
            }
            Notify.failure(this.genericErrorMsgValue)
        } finally {
            Loading.remove()
        }
    }


}
