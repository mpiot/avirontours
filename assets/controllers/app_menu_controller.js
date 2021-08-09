import { Controller } from 'stimulus';

export default class extends Controller {
    static targets = ['menu'];
    static values = { autoOpen: Boolean }

    connect() {
        // On xl screen, display the menu automatically
        if (true === this.autoOpenValue && window.innerWidth >= 1200) {
            this.element.classList.add('nav-app-menu-show');
        }
    }

    toggle(event) {
        event.stopPropagation();
        this.element.classList.toggle('nav-app-menu-show');
    }

    main() {
        if (window.innerWidth >= 1200) {
            return;
        }

        if (false === this.element.classList.contains('nav-app-menu-show')) {
            return;
        }

        this.element.classList.remove('nav-app-menu-show');
    }
}
