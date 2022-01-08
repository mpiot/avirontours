import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu'];

    toggle(event) {
        event.stopPropagation();
        this.element.classList.toggle('nav-account-menu-show');
    }

    main() {
        if (false === this.element.classList.contains('nav-account-menu-show')) {
            return;
        }

        this.element.classList.remove('nav-account-menu-show');
    }
}
