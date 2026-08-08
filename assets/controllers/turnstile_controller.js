import { Controller } from '@hotwired/stimulus';

const SCRIPT_SELECTOR = 'script[src^="https://challenges.cloudflare.com/turnstile/"]';

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
        if (window.turnstile) {
            this.renderWidget();

            return;
        }

        // Turbo appends the Cloudflare script without waiting for it, so on a Turbo visit
        // we connect before it has loaded.
        document.querySelector(SCRIPT_SELECTOR)?.addEventListener('load', () => {
            if (this.element.isConnected) {
                this.renderWidget();
            }
        }, { once: true });
    }

    disconnect () {
        if (undefined !== this.turnstileId) {
            turnstile.remove(this.turnstileId);
        }
    }

    renderWidget () {
        this.turnstileId = turnstile.render(this.containerTarget, {
            action: this.actionValue,
            'error-callback': () => turnstile.reset(this.turnstileId),
            'expired-callback': () => turnstile.reset(this.turnstileId),
            sitekey: this.siteKeyValue,
            theme: this.themeValue
        });
    }
}
