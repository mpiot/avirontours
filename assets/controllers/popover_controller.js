import { Controller } from '@hotwired/stimulus';
import { Popover } from 'bootstrap';

export default class extends Controller {
    static values = {
        options: Object
    };

    /**
     * @property {object} optionsValue
     */

    connect () {
        this.initPopover();
    }

    initPopover () {
        return new Popover(this.element, this.optionsValue);
    }
}
