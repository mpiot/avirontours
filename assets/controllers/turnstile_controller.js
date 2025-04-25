import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['container'];
    static values = {
        action: String,
        siteKey: String,
        theme: String
    };

    /* eslint-disable no-undef */
    /**
     * @type {object} turnstile
     * @property {HTMLFormElement} containerTarget
     * @property {string} siteKeyValue
     * property {string} actionValue
     * property {string} themeValue
     */

    turnstileId;

    connect () {
        turnstile.ready(() => {
            this.turnstileId = turnstile.render(this.containerTarget, {
                action: this.actionValue,
                sitekey: this.siteKeyValue,
                theme: this.themeValue
            });
        });
    }

    disconnect () {
        turnstile.remove(this.turnstileId);
    }
}
