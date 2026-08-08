import { Controller } from '@hotwired/stimulus';
import { Toast } from 'bootstrap';

export default class extends Controller {
    static values = {
        initShow: Boolean,
        options: Object,
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

        // Nothing replaces the stack anymore — streams only append to it — so a hidden toast
        // removes its own node.
        this.element.addEventListener('hidden.bs.toast', () => this.element.remove(), { once: true });

        if (false === this.hasInitShowValue || true === this.initShowValue) {
            this.show();
        }
    }

    show () {
        this.#toast.show();
    }

    dismiss () {
        this.#toast.hide();
    }
}
