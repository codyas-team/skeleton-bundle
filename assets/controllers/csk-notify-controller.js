import { Controller } from '@hotwired/stimulus';
import {Notify} from "notiflix";

export default class extends Controller {

    static values = {
        alerts: Array
    }

    connect() {

    }

    alertsValueChanged(current, previous){
        if (current === undefined || !current){
            return
        }
        for (const rawAlert of this.alertsValue) {
            const alert = JSON.parse(rawAlert)
            switch (alert.type){
                case 'success' :
                    Notify.success(alert.msg)
                    break;
                case 'danger' :
                    Notify.failure(alert.msg)
                    break;
            }
        }
    }

}
