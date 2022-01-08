import { Controller } from '@hotwired/stimulus';
import { Popover } from 'bootstrap';

/* stimulusFetch: "lazy" */
export default class extends Controller {
    static values = { options: Object };

    connect() {
        new Popover(this.element, this.hasOptionsValue ? this.optionsValue : {});
    }
}
