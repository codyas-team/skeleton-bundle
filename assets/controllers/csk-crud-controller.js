import {Controller} from '@hotwired/stimulus';
import {Loading, Notify} from "notiflix";
import {ValidationError} from "../common/ValidationError";

export default class extends Controller {
    deleteDialog = null;
    static targets = ['deleteDialog']
    static values = {
        deleteCandidatePayload: Object,
        genericDeleteMsg: String,
        crudMode: String,
    }

    connect() {
    }

    showDeleteDialog({detail}) {
        this.deleteCandidatePayloadValue = detail
        this.deleteDialog = new bootstrap.Modal(this.deleteDialogTarget, {
            backdrop: 'static'
        })
        this.deleteDialog.show()
    }

    async sortByColumn(evt) {
        const url = new URL(window.location.href);
        const params = url.searchParams;
        const dataset = evt.currentTarget.dataset;
        let direction = 'desc';
        switch (dataset.direction) {
            case "desc":
                direction = 'asc';
                break;
            case "asc":
                direction = 'desc';
                break;
        }
        evt.currentTarget.dataset.direction = await direction;
        params.set('orderColumn', dataset.sort);
        params.set('orderDirection', direction);
        Turbo.visit(url.toString(), { action: "replace" })
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
                    token: this.deleteCandidatePayloadValue.token
                })
            })
            if (!response.ok) {
                const errorResponse = response.status === 400 ? await response.json() : null
                let message = errorResponse.msg !== undefined ? errorResponse.msg : this.genericDeleteMsgValue
                throw new ValidationError(message, null, errorResponse.form)
            }
            const json = await response.json()
            if (json.msg !== undefined){
                Notify.success(json.msg)
            }
            if (json.triggerEvent !== undefined && json.triggerEvent === true){
                this.dispatch('record_deleted');
                this.deleteDialog.hide();
                return;
            }
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
