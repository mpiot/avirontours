import { Controller } from 'stimulus';

export default class extends Controller {
    static values = { buttonId: String, buttonText: String, fullWidth: Boolean, label: String, numberEntriesAtInit: Number }

    initialize() {
        this.index = 0;
        this.addButton = null;
    }

    connect() {
        // Initialize the index
        this.index = this.element.childNodes.length;

        // Add a button
        this.#createAddButton();
        this.element.append(this.addButton);

        // Add a remove button to existing entries
        if (this.index > 0) {
            let entries = this.element.querySelectorAll(':scope > fieldset.form-group, :scope > div.form-group');
            for (let i = 0; i < entries.length; i++) {
                this.#formatEntry(entries[i]);
            }
        }

        // Create default field(s) if needed
        if (0 === this.index && this.numberEntriesAtInitValue > 0) {
            for (let i = 0; i < this.numberEntriesAtInitValue; ++i) {
                this.addEntry(null, false);
            }
        }
    }

    addEntry(event, buttonAction = true) {
        if (event) {
            event.preventDefault();
        }

        let entry = this.#createEntryFromPrototype();
        this.#formatEntry(entry);

        this.element.insertBefore(entry, this.addButton);

        if (true === buttonAction) {
            entry.querySelector('input, select').focus();
        }
    }

    removeEntry(event) {
        event.preventDefault();

        this.element.removeChild(event.target.closest('fieldset, div.form-group'));
    }

    #formatEntry(entry) {
        // Display flex the form fieldset
        entry.classList.add('d-flex');

        // Continue to fill the page
        let divWrapper = entry.querySelector(':scope > div');
        if (divWrapper) {
            divWrapper.classList.add('flex-fill');
        }

        // Modify the legend for non-dynamic entries
        let labelWrapper = entry.querySelector('legend.col-form-label');
        let label = entry.querySelector('span.label')
        if (label && /^\d+$/.test(label.innerHTML)) {
            if (this.hasLabelValue) {
                label.innerHTML = this.labelValue + (Number(label.innerHTML) + 1);
            } else {
                labelWrapper.remove();
            }
        }

        // Create the button
        let button = this.#createRemoveEntryButton();
        button.classList.add('align-self-end', 'mb-3', 'ml-3');

        if (true === this.fullWidthValue) {
            entry.classList.add('justify-content-between');
        }

        // Add remove button
        entry.append(button);
    }

    #createAddButton() {
        let button = document.createElement('button');

        // If we define a specific id for the button
        if (this.hasButtonIdValue) {
            button.id = this.buttonIdValue;
        }

        button.setAttribute('class', 'btn btn-outline-secondary btn-sm');
        button.setAttribute('data-action', 'click->collection-type#addEntry');

        let span = document.createElement('span');
        span.setAttribute('class', 'fas fa-plus');

        if (false === this.hasButtonTextValue) {
            this.buttonTextValue = 'Ajouter';
        }

        button.innerHTML = span.outerHTML + ' ' + this.buttonTextValue;

        this.addButton = button;

        return button;
    }

    #createRemoveEntryButton() {
        let button = document.createElement('button');

        button.setAttribute('class', 'btn btn-danger btn-sm');
        button.setAttribute('data-action', 'click->collection-type#removeEntry');

        let span = document.createElement('span');
        span.setAttribute('class', 'fas fa-trash-alt');
        button.innerHTML = span.outerHTML;

        return button;
    }

    #createEntryFromPrototype() {
        let entry = document.createElement('div');
        entry.innerHTML = this.element.dataset.prototype
            .replace(/class="col-sm-2 control-label required"/, 'class="col-sm-2 control-label"')
            .replace(/__name__label__/g, (this.labelValue ?? '') + (this.index + 1))
            .replace(/__name__/g, this.index)
        ;

        this.index ++;

        return entry.firstChild;
    }
}
