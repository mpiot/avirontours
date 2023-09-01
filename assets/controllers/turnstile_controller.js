import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['container'];
    static values = {
        siteKey: String,
        action: String,
        theme: String
    };

    /**
     * @type {Object} turnstile
     * @property {HTMLFormElement} containerTarget
     * @property {string} siteKeyValue
     * property {string} actionValue
     * property {string} themeValue
     */

    turnstileId;

    connect () {
        turnstile.ready(() => {
            this.turnstileId = turnstile.render(this.containerTarget, {
                sitekey: this.siteKeyValue,
                action: this.actionValue,
                theme: this.themeValue
            });
        });
    }

    disconnect () {
        turnstile.remove(this.turnstileId);
    }
}
