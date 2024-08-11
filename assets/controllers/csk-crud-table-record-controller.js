import {Controller} from '@hotwired/stimulus';

export default class extends Controller {

    static targets = []
    static values = {
        deleteUrl: String,
        deleteToken: String
    }

    connect() {

    }

    requestDelete() {
        this.dispatch('deleteRecord', {detail: {url: this.deleteUrlValue, token: this.deleteTokenValue}})
    }

    requestDialog(event) {
        let dataset = event.currentTarget.dataset
        this.dispatch('requestDialog', {
            detail: {
                dialogId: dataset.dialogId,
                loadUrl: dataset.loadUrl
            }
        })
    }


}
