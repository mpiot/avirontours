import { Controller } from '@hotwired/stimulus';
import axios from 'axios';
import { useDebounce } from 'stimulus-use';

export default class extends Controller {
    static targets = [
        'target'
    ];

    static debounces = [
        {
            name: 'denouncedChange',
            wait: 400
        },
        'denouncedKeyup'
    ];

    /**
     * @type {HTMLInputElement[]|HTMLSelectElement[]} targetTargets
     */

    connect () {
        useDebounce(this, { wait: 800 });
    }

    /**
     * @param {UIEvent} event
     * @returns {void}
     */
    change (event) {
        this.denouncedChange(event);
    }

    /**
     * @param {UIEvent} event
     * @returns {void}
     */
    denouncedChange (event) {
        this.updateTarget(event);
    }

    /**
     * @param {UIEvent} event
     * @returns {void}
     */
    input (event) {
        this.updateTarget(event);
    }

    /**
     * @param {UIEvent} event
     * @returns {void}
     */
    keyup (event) {
        this.denouncedKeyup(event);
    }

    /**
     * @param {UIEvent} event
     * @returns {void}
     */
    denouncedKeyup (event) {
        this.updateTarget(event);
    }

    /**
     * @param {UIEvent} event
     * @returns {void}
     * @private
     */
    async updateTarget (event) {
        const form = event.target.closest('form');
        const formData = new FormData(form);

        // Remove the CSRF token from data
        for (const key of formData.keys()) {
            if (key.includes('[_token]')) {
                formData.delete(key);
            }
        }

        try {
            const response = await axios({
                method: form.method,
                url: form.action,
                data: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            this.replaceTargetContent(response.data);
        } catch (error) {
            this.replaceTargetContent(error.response.data);
        }
    }

    /**
     * @param {string} content
     * @returns {void}
     * @private
     */
    replaceTargetContent (content) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(content, 'text/html');

        this.targetTargets.forEach(function (target) {
            let selector = `#${target.id}`;
            if ('' === target.id) {
                selector = '[data-dependent-field-target="target"]';
            }

            const replacement = doc.querySelector(selector);
            if (null === replacement) {
                target.innerHTML = '';
            } else {
                target.outerHTML = replacement.outerHTML;
            }
        });
    }
}
