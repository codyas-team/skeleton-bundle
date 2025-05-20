import {Controller} from '@hotwired/stimulus';
import Swal from "sweetalert2";
import {Notify} from "notiflix";

export default class extends Controller {

    static targets = [
        'dialog', 'placeholder', 'content', 'pagination', 'saveButton',
        'saveButtonIcon', 'dialogBody', 'filterAmount', 'filterReference'
    ]
    static outlets = ['csk-datatable-item']
    static values = {
        loadUrl: String,
        filterUrl: String,
        processUrl: String,
        csrfToken: String,
        confirmTitle: String,
        confirmText: String,
        filter: String,
        filterStatus: String,
    }

    connect() {
        this.loadContent()
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

    loadContent() {
        this.showPlaceholder()
        this.fetchContent()
    }

    changePage(evt) {
        evt.preventDefault()
        this.loadUrlValue = evt.target.getAttribute('href')
        this.loadContent()
    }

    showPlaceholder() {
        this.placeholderTarget.classList.replace('d-none', 'd-flex')
    }

    hidePlaceholder() {
        this.placeholderTarget.classList.replace('d-flex', 'd-none')
    }

    fetchContent() {
        const params = {
            reference: this.hasFilterReferenceTarget ? this.filterReferenceTarget.value : '',
            amount: this.hasFilterAmountTarget ? this.filterAmountTarget.value : '',
            filter: this.filterValue,
            status: this.filterStatusValue,
        };
        const url = new URL(this.loadUrlValue);
        Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
        fetch(url)
            .then(response => response.json())
            .then(data => {
                this.contentTarget.innerHTML = data.content
                this.paginationTarget.innerHTML = data.pagination
                this.hidePlaceholder()
                // $('[data-toggle="popover"]', $(this.contentTarget)).popover({
                //     container: 'body'
                // })
                // $('[data-toggle="tooltip"]', $(this.contentTarget)).tooltip()
            })
            .catch(res => {
                console.error(res)
                Notify.failure("Something went wrong while loading the data.");
            })
    }

    processPage() {
        let payload = this.validateAndBuildPageRequest()
        if (payload.length === 0) {
            new PNotify({
                title: "Heads up!",
                text: "Please check the current page selections, you need to include at least one valid categorized record.",
                type: 'warning'
            });
            return;
        }
        Swal.fire({
            title: this.confirmTitleValue,
            text: this.confirmTextValue,
            icon: "question",
            showCancelButton: true,
        }).then((result) => {
            if (result.value) {
                this.processPayload(payload);
            }
        });

    }

    processPayload(payload) {
        this.showMask()
        fetch(this.processUrlValue, {
            method: 'POST',
            body: JSON.stringify({
                token: this.csrfTokenValue,
                lineItems: payload
            })
        })
            .then(response => response.json())
            .then(data => {
                this.hideMask()
                this.loadContent()
            })
            .catch((error) => {
                this.hideMask()
                console.error('Error:', error)
            })
    }

    validateAndBuildPageRequest() {
        let payload = [];
        let errorsFound = false;
        for (const lineItemOutlet of this.payoutCategorizeItemOutlets) {
            if (lineItemOutlet.isIgnored()) {
                continue;
            }
            if (lineItemOutlet.commandValue === 'include'){
                if (!lineItemOutlet.categoryValue) {
                    lineItemOutlet.maskSelectControlError(lineItemOutlet.categoryTarget)
                    errorsFound = true;
                }
                if (!lineItemOutlet.issuerValue) {
                    lineItemOutlet.maskSelectControlError(lineItemOutlet.issuerTarget)
                    errorsFound = true;
                }
            }
            if (errorsFound === true){
                continue;
            }

            payload.push(lineItemOutlet.generatePayload())
        }
        return errorsFound ? [] : payload
    }

    showMask() {
        this.saveButtonIconTarget.classList.replace('fa-save', 'fa-spinner-third')
        this.saveButtonIconTarget.classList.add('fa-spin')
        this.saveButtonTarget.setAttribute('disabled', true)
    }

    hideMask() {
        this.saveButtonIconTarget.classList.replace('fa-spinner-third', 'fa-save')
        this.saveButtonIconTarget.classList.remove('fa-spin')
        this.saveButtonTarget.removeAttribute('disabled')
    }

    attemptFilter(evt) {
        if (evt.which === 13) {
            this.loadUrlValue = this.filterUrlValue
            this.loadContent()
        }
    }

    standByAll(){
        for (const lineItemOutlet of this.payoutCategorizeItemOutlets) {
            if (lineItemOutlet.statusValue === 'processed') {
                continue
            }
            lineItemOutlet.markAsStandBy()
        }
    }

    includeAll(){
        for (const lineItemOutlet of this.payoutCategorizeItemOutlets) {
            if (lineItemOutlet.statusValue === 'processed') {
                continue
            }
            lineItemOutlet.markAsIncluded()
        }
    }

    excludeAll(){
        for (const lineItemOutlet of this.payoutCategorizeItemOutlets) {
            if (lineItemOutlet.statusValue === 'processed') {
                continue
            }
            lineItemOutlet.markAsExcluded()
        }

    }

    setFilter(evt) {
        if (!evt.target.dataset.filterValue){
            return;
        }
        this.filterValue = evt.target.dataset.filterValue
        this.loadUrlValue = this.filterUrlValue
        this.loadContent()
    }

    handleStatusFilterChange(evt){
        let value = evt.target.dataset.filterValue
        if (!value){
            return
        }
        if (value === 'remove'){
            this.filterStatusValue = ''
        } else{
            this.filterStatusValue = value
        }
        this.loadContent()
    }


}
