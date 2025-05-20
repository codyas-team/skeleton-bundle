import {Controller} from '@hotwired/stimulus';

export default class extends Controller {

    static targets = []
    static values = {
    }

    connect() {

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
