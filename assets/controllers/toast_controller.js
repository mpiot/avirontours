import { Controller } from '@hotwired/stimulus';
import { Toast } from 'bootstrap';

/* stimulusFetch: "lazy" */
export default class extends Controller {
    static values = {
        options: Object,
        initShow: Boolean
    };

    connect() {
        this.toast = new Toast(this.element, this.hasOptionsValue ? this.optionsValue : {});

        if (false === this.hasInitShowValue || true === this.initShowValue) {
            this.show();
        }
    }

    show() {
        this.toast.show();
    }

    dismiss() {
        this.toast.dispose();
    }
}
