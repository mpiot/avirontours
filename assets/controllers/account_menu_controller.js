import { Controller } from 'stimulus';

export default class extends Controller {
    static targets = ['menu'];

    toggle(event) {
        event.stopPropagation();
        this.menuTarget.classList.toggle('nav-account-menu-show');
    }

    main() {
        if (false === this.menuTarget.classList.contains('nav-account-menu-show')) {
            return;
        }

        this.menuTarget.classList.remove('nav-account-menu-show');
    }
}
