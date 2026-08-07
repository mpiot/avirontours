import { Controller } from '@hotwired/stimulus';
import { useDebounce } from 'stimulus-use';

/** @class HTMLElement */
export default class extends Controller {
    static readonly targets = [
        'target'
    ];

    static readonly debounces = [
        {
            name: 'denouncedChange',
            wait: 400
        },
        'denouncedKeyup'
    ];

    declare readonly targetTargets: HTMLElement[];
    declare readonly targetTarget: HTMLElement;

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
        const scope = event.params?.scope ?? null;
        const form = event.target.closest('form');
        const formData = new FormData(form);
        for (const key of Array.from(formData.keys())) {
            if (key.includes('[_token]') || 'cf-turnstile-response' === key) {
                formData.delete(key);

                continue;
            }

            if (formData.get(key) instanceof File) {
                formData.delete(key);
            }
        }

        const focusedId = document.activeElement?.id ?? '';

        this.setBusy(scope, true);

        try {
            const response = await fetch(form.action, {
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                method: form.method,
            });
            const data = await response.text();

            this.replaceTargetContent(scope, data);
        } finally {
            this.setBusy(scope, false);
        }

        if ('' !== focusedId && document.activeElement?.id !== focusedId) {
            document.getElementById(focusedId)?.focus();
        }
    }

    /**
     * @param {?string} scope
     * @returns {HTMLElement[]}
     * @private
     */
    scopedTargets (scope) {
        if (null === scope) {
            return this.targetTargets;
        }

        return this.targetTargets.filter(function (target) {
            return target.dataset.dependentFieldScope === scope;
        });
    }

    /**
     * @param {?string} scope
     * @param {boolean} busy
     * @returns {void}
     * @private
     */
    setBusy (scope, busy) {
        this.scopedTargets(scope).forEach(function (target) {
            if (busy) {
                target.setAttribute('aria-busy', 'true');
            } else {
                target.removeAttribute('aria-busy');
            }
        });
    }

    /**
     * @param {?string} scope
     * @param {string} content
     * @returns {void}
     * @private
     */
    replaceTargetContent (scope, content) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(content, 'text/html');

        this.scopedTargets(scope).forEach(function (target) {
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
