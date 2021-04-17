import { Controller } from 'stimulus';
import { Offcanvas } from 'bootstrap';

export default class extends Controller {
    static targets = ['sidebar'];
    static values = { 'display': Boolean }

    connect() {
        // Init the sidebar
        this.sidebar = new Offcanvas(this.sidebarTarget, {
            backdrop: false,
            keyboard: false,
            scroll: true
        })

        // On xl screen, display the menu automatically
        if (window.innerWidth >= 1200) {
            this.displayValue = true;
            this.sidebar.show();
        }

        // Add an event listener to catch hide event
        let _this = this;
        this.sidebarTarget.addEventListener('hide.bs.offcanvas', function(event) {
            if (true === _this.displayValue && window.innerWidth >= 1200) {
                event.preventDefault();
            }
        })
    }

    toggle(event) {
        event.stopPropagation();
        this.displayValue = !this.displayValue;
        this.sidebar.toggle();
    }
}
