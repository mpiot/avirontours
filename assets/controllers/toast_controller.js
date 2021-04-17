import { Controller } from 'stimulus';
import { Toast } from 'bootstrap';

export default class extends Controller {
    static values = { 'delay': Number }

    connect() {
        console.log(this.delayValue);

        this.toast = new Toast(this.element, {
            delay: this.delayValue,
        });
        this.toast.show();
    }

    dismiss() {
        this.toast.dispose();
    }
}
