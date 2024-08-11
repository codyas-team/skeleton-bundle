import {Controller} from '@hotwired/stimulus';
import hyperform from 'hyperform';
import {Block, Notify} from "notiflix";
import {ValidationError} from "../common/ValidationError";

export default class extends Controller {

    validator;
    static targets = ['form', 'formContainer']
    static values = {
        actionUrl: String,
        genericErrorMsg: String,
    }

    connect() {
        this.validator = hyperform(this.formTarget, {
            classes: {
                invalid: 'is-invalid',
                warning: 'text-danger',
            }
        })
    }

    async handleSubmit(e) {
        e.preventDefault()
        if (!this.formTarget.reportValidity()) {
            return
        }
        Block.standard([this.formTarget])
        const headers = new Headers()
        headers.append("Accept", "application/json");
        try{
            const response = await fetch(this.formTarget.getAttribute('action'), {
                method: 'POST',
                body: new FormData(this.formTarget),
                headers: headers
            })
            if (!response.ok){
                const errorResponse = response.status === 400 ? await response.json() : null
                let message = errorResponse.msg !== undefined ? errorResponse.msg : this.genericErrorMsgValue;
                throw new ValidationError(message, null, errorResponse.form);
            }
            const json = await response.json()
            Turbo.visit(json.instanceUrl)
        } catch(error){
            if (error instanceof ValidationError){
                Notify.warning(error.message)
                this.formContainerTarget.innerHTML = error.parameters
                return
            }
            Notify.failure(this.genericErrorMsgValue)
        }finally {
            Block.remove([this.formTarget])
        }
    }

}
