import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu'];
    static values = { autoOpen: Boolean };

    connect () {
        // On xl screen, display the menu automatically
        if (true === this.autoOpenValue && 1200 < window.innerWidth) {
            this.element.classList.add('nav-app-menu-show');
        }
    }

    toggle (event) {
        event.stopPropagation();
        this.element.classList.toggle('nav-app-menu-show');
    }

    main () {
        if (1200 < window.innerWidth) {
            return;
        }

        if (false === this.element.classList.contains('nav-app-menu-show')) {
            return;
        }

        this.element.classList.remove('nav-app-menu-show');
    }
}
