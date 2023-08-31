import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['container'];
    static values = {
        siteKey: String
    };

    /**
     * @type {Object} turnstile
     * @property {string} siteKeyValue
     * @property {HTMLFormElement} containerTarget
     */

    turnstileId;

    connect () {
        console.log('we');
        turnstile.ready(() => {
            this.turnstileId = turnstile.render(this.containerTarget, {
                sitekey: this.siteKeyValue,
            });
        });
    }

    disconnect () {
        turnstile.remove(this.turnstileId);
    }
}
