import {Controller} from '@hotwired/stimulus';

export default class extends Controller {

    static values = {
        controlField: String,
    }

    connect() {

    }

    handle(evt) {
        const controlFieldElement = document.getElementById(this.controlFieldValue);
        if (this.element.checked) {
            this.setCurrentDateToInput(controlFieldElement);
        } else {
            controlFieldElement.value = "";
        }
    }

    setCurrentDateToInput(element) {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0'); // Months are 0-indexed
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2,'0');
        const minutes = String(now.getMinutes()).padStart(2,'0');
        const seconds = String(now.getSeconds()).padStart(2,'0');

        const formattedDateTime = `${year}-${month}-${day}T${hours}:${minutes}:${seconds}`;
        element.value = formattedDateTime;
    }

}
