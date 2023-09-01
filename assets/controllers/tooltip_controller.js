import { Controller } from '@hotwired/stimulus';
import { Tooltip } from 'bootstrap';

export default class extends Controller {
    static values = {
        options: Object
    };

    /**
     * @property {object} optionsValue
     */

    connect () {
        this.initTooltip();
    }

    initTooltip () {
        return new Tooltip(this.element, this.optionsValue);
    }
}
