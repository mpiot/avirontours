import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';

require('select2');
require('select2/dist/css/select2.min.css');
require('@ttskch/select2-bootstrap4-theme/dist/select2-bootstrap4.min.css');

/* stimulusFetch: "lazy" */
export default class extends Controller {
    static  targets = ["suffix"];

    initialize() {
        $.fn.select2.defaults.set("theme", "bootstrap4");
    }

    connect() {
        $( this.element ).select2({
            templateResult: this.formatState,
        });

        // Fire native event on select2 change
        $( this.element ).on('select2:select', function () {
            let event = new Event('change', { bubbles: true })
            this.dispatchEvent(event);
        });
    }

    formatState(state) {
        const suffix = $(state.element).data('select2-suffix');

        if (undefined === suffix) {
            return state.text;
        }

        return $(document.createTextNode(state.text)).add($(suffix));
    }
}
