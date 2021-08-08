import { Controller } from 'stimulus';

export default class extends Controller {
    static targets = ['menu'];

    connect() {
        // On xl screen, display the menu automatically
        if (window.innerWidth >= 1200) {
            this.menuTarget.classList.add('nav-app-menu-show');
        }
    }

    toggle(event) {
        event.stopPropagation();
        this.menuTarget.classList.toggle('nav-app-menu-show');
    }

    main() {
        if (window.innerWidth >= 1200) {
            return;
        }

        if (false === this.menuTarget.classList.contains('nav-app-menu-show')) {
            return;
        }

        this.menuTarget.classList.remove('nav-app-menu-show');
    }
}
