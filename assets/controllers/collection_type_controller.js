import { Controller } from 'stimulus';

export default class extends Controller {
    static values = {
        buttonId: String,
        buttonText: String,
        label: String,
        numberEntriesAtInit: Number
    }

    initialize() {
        this.index = 0;
        this.entryAddLink = null;
    }

    connect() {
        // Initialize the index
        this.index = this.element.childNodes.length;

        // Insert an add entry link
        this.appendEntryAddLink();

        // Process existing entries
        this.processExistingEntries();

        // Create default field(s) if needed
        if (0 === this.index && this.numberEntriesAtInitValue > 0) {
            for (let i = 0; i < this.numberEntriesAtInitValue; ++i) {
                this.addEntry(null, false);
            }
        }
    }

    processExistingEntries() {
        if (this.index === 0) {
            return;
        }

        let entries = this.element.querySelectorAll(':scope > fieldset.mb-3, :scope > div.mb-3');
        for(let entry of entries) {
            this.appendEntryRemoveLink(entry);
            this.processEntryLabel(entry);
        }
    }

    addEntry(event, focusField = true) {
        if (event) {
            event.preventDefault();
        }

        // Get the entry content
        let entry = this.getEntryContent();
        this.appendEntryRemoveLink(entry);
        this.processEntryLabel(entry);

        // Insert the new entry in DOM
        this.element.insertBefore(entry, this.entryAddLink);

        try {
            if (true === focusField) {
                const fields = this.element.querySelectorAll(':scope > fieldset, :scope > div');
                fields[fields.length - 1].querySelector('input, select').focus();
            }
        } catch (exception) {
            console.log(exception);
        }

        // Update the index
        this.index++;
    }

    removeEntry(event) {
        event.preventDefault();
        let entry = event.target.closest('fieldset, div.mb-3');

        this.element.removeChild(entry);
    }

    appendEntryAddLink() {
        let button = document.createElement('button');

        button.type =  'button';
        button.className = 'btn btn-outline-secondary btn-sm align-self-end ';
        button.innerHTML = `<span class="fas fa-plus"></span> ${ this.hasButtonTextValue ? this.buttonTextValue : 'Ajouter' }`;
        button.setAttribute('data-action', 'click->collection-type#addEntry');

        // If we define a specific id for the button
        if (this.hasButtonIdValue) {
            button.id = this.buttonIdValue;
        }

        let div = document.createElement('div');
        div.className = 'w-100';
        div.append(button);

        this.entryAddLink = div;
        this.element.append(this.entryAddLink);
    }

    appendEntryRemoveLink(entry) {
        let button = document.createElement('button');

        button.type = 'button';
        button.className = 'btn btn-danger btn-sm align-self-end ms-3 mb-3';
        button.innerHTML = `<span class="fas fa-trash-alt"></span>`;
        button.setAttribute('data-action', 'click->collection-type#removeEntry');

        let fieldDiv = entry.querySelector('fieldset > div, div > input, div > select');
        entry.querySelector('fieldset > div, div > input, div > select').outerHTML = '<div class="d-flex">' + fieldDiv.outerHTML + button.outerHTML + '</div>';
    }

    processEntryLabel(entry) {
        let legend = entry.querySelector('legend');

        if (null !== legend && false === this.hasLabelValue) {
            legend.remove();
        }

        if (null !== legend && legend.innerText.match(/^[0-9]+$/)) {
            legend.innerText = `${this.labelValue} ${parseInt(legend.innerText) + 1}`;
        }

        return entry;
    }

    getEntryContent() {
        let prototype = this.element.dataset.prototype;
        prototype = prototype
            .replace(/__name__label__/g, `${this.labelValue ?? ''} ${this.index + 1}`)
            .replace(/__name__/g, this.index)
        ;

        const template = document.createElement('template');
        template.innerHTML = prototype;

        return template.content
    }
}
