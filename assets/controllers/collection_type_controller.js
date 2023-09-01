import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';

/** @class HTMLElement */
export default class extends Controller {
    static values = {
        buttonId: String,
        buttonText: String,
        label: String,
        numberEntriesAtInit: { type: Number, default: 1 },
        prototypeName: String
    };

    /**
     * @property {string} buttonIdValue
     * @property {boolean} hasButtonIdValue
     * @property {string} buttonTextValue
     * @property {boolean} hasButtonTextValue
     * @property {boolean} hasLabelValue
     * @property {string} labelValue
     * @property {number} numberEntriesAtInitValue
     * @property {string} prototypeNameValue
     * @property {boolean} hasPrototypeNameValue
     */

    /**
     * @type {number}
     */
    #index = 0;

    /**
     * @type {HTMLElement|null}
     */
    #entryAddLink = null;

    connect () {
        useDispatch(this);

        // Initialize the index
        this.#index = this.element.childNodes.length;

        // Insert an add entry link
        this.appendEntryAddLink();

        // Process existing entries
        this.processExistingEntries();

        // Create default field(s) if needed
        if (0 === this.#index && 0 < this.numberEntriesAtInitValue) {
            for (let i = 0; i < this.numberEntriesAtInitValue; ++i) {
                this.addEntry(null);
            }
        }
    }

    addEntry (event) {
        if (event) {
            event.preventDefault();
        }

        // Get the entry content
        const entry = this.getEntryContent();
        this.appendEntryRemoveLink(entry);
        this.processEntryLabel(entry);

        // Insert the new entry in DOM
        this.element.insertBefore(entry, this.#entryAddLink);

        // Update the index
        this.#index++;

        // Dispatch an event
        this.dispatch('add-entry');
    }

    addEntryFromField (event) {
        // Avoid create a new field if this is not the last trigger
        const selectors = Array.from(document.querySelectorAll('[data-action*="collection-type#addEntryFromField"]'));
        if (selectors.length !== selectors.indexOf(event.target) + 1) {
            return;
        }

        this.addEntry(event);
    }

    removeEntry (event) {
        event.preventDefault();
        const entry = event.target.closest('fieldset, div.mb-3');

        this.element.removeChild(entry);

        // Dispatch an event
        this.dispatch('remove-entry');
    }

    /**
     * @returns {void}
     * @private
     */
    processExistingEntries () {
        if (0 === this.#index) {
            return;
        }

        const entries = this.element.querySelectorAll(':scope > fieldset.mb-3, :scope > div.mb-3');
        for (const entry of entries) {
            this.appendEntryRemoveLink(entry);
            this.processEntryLabel(entry);
        }
    }

    /**
     * @returns {void}
     * @private
     */
    appendEntryAddLink () {
        const button = document.createElement('button');

        button.type = 'button';
        button.className = 'btn btn-outline-secondary btn-sm align-self-end ';
        button.innerHTML = `<span class="fas fa-plus"></span> ${this.hasButtonTextValue ? this.buttonTextValue : 'Ajouter'}`;
        button.setAttribute('data-action', 'click->collection-type#addEntry');

        // If we define a specific id for the button
        if (this.hasButtonIdValue) {
            button.id = this.buttonIdValue;
        }

        const div = document.createElement('div');
        div.className = 'w-100';
        div.append(button);

        this.#entryAddLink = div;
        this.element.append(this.#entryAddLink);
    }

    /**
     * @param {HTMLFieldSetElement} entry
     * @returns {void}
     * @private
     */
    appendEntryRemoveLink (entry) {
        const button = document.createElement('button');

        button.type = 'button';
        button.className = 'btn btn-danger';
        button.innerHTML = '<span class="fas fa-trash-alt"></span>';
        button.setAttribute('data-action', 'click->collection-type#removeEntry');

        const fieldDiv = entry.querySelector('fieldset > div, div > input, div > select');
        entry.querySelector('fieldset > div, div > input, div > select').outerHTML = '<div class="d-flex"><div class="flex-grow-1">' + fieldDiv.outerHTML + '</div><div class="align-self-end ms-3" style="margin-bottom: 1rem;">' + button.outerHTML + '</div></div>';
    }

    /**
     * @param {HTMLFieldSetElement} entry
     * @returns {void}
     * @private
     */
    processEntryLabel (entry) {
        const legend = entry.querySelector('legend');

        if (null !== legend && false === this.hasLabelValue) {
            legend.remove();
        }

        if (null !== legend && legend.innerText.match(/^\d+$/)) {
            legend.innerText = `${this.labelValue} ${parseInt(legend.innerText) + 1}`;
        }

        return entry;
    }

    /**
     * @returns {void}
     * @private
     */
    getEntryContent () {
        const prototypeName = this.hasPrototypeNameValue ? this.prototypeNameValue : '__name__';
        const labelRegex = new RegExp(prototypeName + 'label__', 'g');
        const nameRegex = new RegExp(prototypeName, 'g');

        let prototype = this.element.dataset.prototype;
        prototype = prototype
            .replace(labelRegex, `${this.labelValue ?? ''} ${this.#index + 1}`)
            .replace(nameRegex, this.#index.toString())
        ;
        prototype = prototype.trim();

        const template = document.createElement('template');
        template.innerHTML = prototype;

        return template.content.firstChild;
    }
}
