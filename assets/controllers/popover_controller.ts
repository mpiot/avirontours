import { Controller } from '@hotwired/stimulus';
import { Popover } from 'bootstrap';

export default class extends Controller {
    static readonly values = {
        options: Object
    };

    declare readonly optionsValue: object;

    private popover: Popover;

    connect () {
        this.popover = new Popover(this.element, this.optionsValue);
    }
}
