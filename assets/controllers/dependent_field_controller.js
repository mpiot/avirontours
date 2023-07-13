import { Controller } from '@hotwired/stimulus';
import axios from "axios";
import { useDebounce } from "stimulus-use";

export default class extends Controller {
    static targets = [
        'target'
    ];

    static debounces = [
        {
            name: 'denouncedChange',
            wait: 400,
        },
        'denouncedKeyup'
    ];

    connect() {
        useDebounce(this, { wait: 800 });
    }

    change(event) {
        this.denouncedChange(event);
    }

    denouncedChange(event) {
        this.#updateTarget(event)
    }

    input(event) {
        this.#updateTarget(event);
    }

    keyup(event) {
        this.denouncedKeyup(event);
    }

    denouncedKeyup(event) {
        this.#updateTarget(event)
    }

    async #updateTarget(event) {
        const form = event.target.closest('form');
        const formData = new FormData(form);

        // Remove the CSRF token from data
        for (let key of formData.keys()) {
            if (key.includes('[_token]')) {
                formData.delete(key);
            }
        }

        try  {
            const response = await axios({
                method: form.method,
                url: form.action,
                data: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            this.#replaceTargetContent(response.data);
        } catch (error) {
            this.#replaceTargetContent(error.response.data);
        }
    }

    #replaceTargetContent(content) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(content, 'text/html');

        console.log(this.targetTargets);

        this.targetTargets.forEach(function(target) {
            let selector = `#${target.id}`;
            if ('' === target.id) {
                selector = '[data-dependent-field-target="target"]';
            }
            console.log(selector);

            let replacement = doc.querySelector(selector);
            if (null === replacement) {
                target.innerHTML = '';
            } else {
                target.outerHTML = replacement.outerHTML;
            }
        })
    }
}
