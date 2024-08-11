import {Controller} from '@hotwired/stimulus';
import {Block, Loading, Notify} from "notiflix";
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

    formTargetConnected(element){
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
        event.preventDefault()
        if (!this.formTarget.reportValidity()) {
            return
        }
        Loading.standard()
        const headers = new Headers()
        headers.append("Accept", "application/json")
        try{
            const response = await fetch(this.formTarget.getAttribute('action'), {
                method: 'POST',
                body: new FormData(this.formTarget),
                headers: headers
            })
            if (!response.ok){
                const errorResponse = response.status === 400 ? await response.json() : null
                let message = errorResponse.msg !== undefined ? errorResponse.msg : this.genericErrorMsgValue;
                throw new ValidationError(message, null, errorResponse.view);
            }
            const json = await response.json()
            Notify.success(json.msg)
            this.dialog.hide()
        } catch(error){
            if (error instanceof ValidationError){
                Notify.warning(error.message)
                this.dialogBodyTarget.innerHTML = error.parameters
                return
            }
            Notify.failure(this.genericErrorMsgValue)
        }finally {
            Loading.remove()
        }
    }


}
