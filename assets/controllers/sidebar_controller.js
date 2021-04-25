import { Controller } from 'stimulus';
import { Offcanvas } from 'bootstrap';

export default class extends Controller {
    static targets = ['sidebar', 'main'];

    connect() {
        // On xl screen, display the menu automatically
        if (window.innerWidth >= 1200) {
            this.sidebarTarget.classList.add('sidebar-show');
        }
    }

    toggle(event) {
        event.stopPropagation();
        this.sidebarTarget.classList.toggle('sidebar-show');
    }

    main() {
        if (window.innerWidth >= 1200) {
            return;
        }

        if (false === this.sidebarTarget.classList.contains('sidebar-show')) {
            return;
        }

        this.sidebarTarget.classList.remove('sidebar-show');
    }
}
