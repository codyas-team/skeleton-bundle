import {Controller} from '@hotwired/stimulus';

export default class extends Controller {

    connect() {

    }

    reset(evt) {
        this.element
            .querySelectorAll(".tomselected")
            .forEach((tomselectedElement) => {
                tomselectedElement.tomselect.clear();
            });
    }

}
