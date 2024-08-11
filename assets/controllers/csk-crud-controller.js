import {Controller} from '@hotwired/stimulus';
import {Loading, Notify} from "notiflix";
import {ValidationError} from "../common/ValidationError";

export default class extends Controller {

    static targets = ['deleteDialog']
    static values = {
        deleteCandidatePayload: Object,
        genericDeleteMsg: String
    }

    connect() {
    }

    showDeleteDialog({detail}) {
        this.deleteCandidatePayloadValue = detail
        const dialog = new bootstrap.Modal(this.deleteDialogTarget, {
            backdrop: 'static'
        })
        dialog.show()
    }

    async deleteRecord(event) {
        if (this.deleteCandidatePayloadValue.url === undefined || !this.deleteCandidatePayloadValue.url) {
            return
        }
        Loading.pulse()
        try {
            const response = await fetch(this.deleteCandidatePayloadValue.url, {
                headers: {
                    'Content-Type': 'application/json'
                },
                method: 'DELETE',
                body: JSON.stringify({
                    token : this.deleteCandidatePayloadValue.token
                })
            })
            if (!response.ok) {
                const errorResponse = response.status === 400 ? await response.json() : null
                let message = errorResponse.msg !== undefined ? errorResponse.msg : this.genericDeleteMsgValue
                throw new ValidationError(message, null, errorResponse.form)
            }
            const json = await response.json()
            Turbo.visit(window.location.href)
        } catch (error) {
            console.error(error)
            if (error instanceof ValidationError) {
                Notify.warning(error.message)
                return
            }
            Notify.failure(this.genericDeleteMsgValue)
        } finally {
            Loading.remove()
        }
    }
}
