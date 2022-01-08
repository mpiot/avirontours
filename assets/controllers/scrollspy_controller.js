import { Controller } from '@hotwired/stimulus';
import { ScrollSpy } from 'bootstrap';

/* stimulusFetch: "lazy" */
export default class extends Controller {
    static values = { options: Object };

    connect() {
        new ScrollSpy(this.element, this.hasOptionsValue ? this.optionsValue : {});
    }
}
