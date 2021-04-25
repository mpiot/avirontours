import { Controller } from 'stimulus';
import { Popover } from 'bootstrap';

export default class extends Controller {
    static values = { options: Object };

    connect() {
        new Popover(this.element, this.hasOptionsValue ? this.optionsValue : {});
    }
}
