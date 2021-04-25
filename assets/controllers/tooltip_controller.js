import { Controller } from 'stimulus';
import { Tooltip } from 'bootstrap';

export default class extends Controller {
    static values = { options: Object };

    connect() {
        new Tooltip(this.element, this.hasOptionsValue ? this.optionsValue : {});
    }
}
