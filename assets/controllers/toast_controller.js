import { Controller } from '@hotwired/stimulus';
import { Toast } from 'bootstrap';

export default class extends Controller {
    static values = {
        options: Object,
        initShow: Boolean
    };

    /**
     * @property {object} optionsValue
     * @property {boolean} initShowValue
     * @property {boolean} hasInitShowValue
     */

    /** @type {Toast} */
    #toast;

    connect () {
        this.#toast = new Toast(this.element, this.optionsValue);

        if (false === this.hasInitShowValue || true === this.initShowValue) {
            this.show();
        }
    }

    show () {
        this.#toast.show();
    }

    dismiss () {
        this.#toast.dispose();
    }
}
