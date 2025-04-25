import { Controller } from '@hotwired/stimulus';

/** @class HTMLElement */
export default class extends Controller {
    static readonly values = {
        buttonId: String,
        buttonText: String,
        label: String,
        numberEntriesAtInit: { default: 1, type: Number },
        prototypeName: String
    };

    declare readonly buttonIdValue: string;
    declare readonly hasButtonIdValue: boolean;
    declare readonly buttonTextValue: string;
    declare readonly hasButtonTextValue: boolean;
    declare readonly hasLabelValue: boolean;
    declare readonly labelValue: string;
    declare readonly numberEntriesAtInitValue: number;
    declare readonly prototypeNameValue: string;
    declare readonly hasPrototypeNameValue: boolean;

    private index: number = 0;
    private entryAddLink: HTMLElement|null = null;

    connect () {
        // Initialize the index
        this.index = this.element.childNodes.length;

        // Insert an add entry link
        this.appendEntryAddLink();

        // Process existing entries
        this.processExistingEntries();

        // Create default field(s) if needed
        if (0 === this.index && 0 < this.numberEntriesAtInitValue) {
            for (let i = 0; i < this.numberEntriesAtInitValue; ++i) {
                this.addEntry(null);
            }
        }
    }

    addEntry (event: Event): void {
        if (event) {
            event.preventDefault();
        }

        // Get the entry content
        const entry = this.getEntryContent();
        this.appendEntryRemoveLink(entry);
        this.processEntryLabel(entry);

        // Insert the new entry in DOM
        this.element.insertBefore(entry, this.entryAddLink);

        // Update the index
        this.index++;
    }

    addEntryFromField (event: Event): void {
        // Avoid create a new field if this is not the last trigger
        const selectors: HTMLElement[] = Array.from(document.querySelectorAll('[data-action*="collection-type#addEntryFromField"]'));

        const triggerElement = event.target;
        if (
            !(triggerElement instanceof HTMLElement)
            || selectors.length !== (selectors.indexOf(triggerElement) + 1)
        ) {
            return;
        }

        this.addEntry(event);
    }

    removeEntry (event: Event): void {
        event.preventDefault();
        const element: HTMLElement = event.target as HTMLElement;
        const entry = element.closest('fieldset, div.mb-3');

        this.element.removeChild(entry);
    }

    private processExistingEntries (): void {
        if (0 === this.index) {
            return;
        }

        const entries: NodeListOf<HTMLElement> = this.element.querySelectorAll(':scope > fieldset.mb-3, :scope > div.mb-3');
        for (const entry of entries) {
            this.appendEntryRemoveLink(entry);
            this.processEntryLabel(entry);
        }
    }

    private appendEntryAddLink (): void {
        const button = document.createElement('button');

        button.type = 'button';
        button.className = 'btn btn-outline-secondary btn-sm align-self-end ';
        button.innerHTML = `${this.getPlusIcon()} ${this.hasButtonTextValue ? this.buttonTextValue : 'Ajouter'}`;
        button.setAttribute('data-action', 'click->collection-type#addEntry');

        // If we define a specific id for the button
        if (this.hasButtonIdValue) {
            button.id = this.buttonIdValue;
        }

        const div = document.createElement('div');
        div.className = 'w-100';
        div.append(button);

        this.entryAddLink = div;
        this.element.append(this.entryAddLink);
    }

    private appendEntryRemoveLink (entry: HTMLElement): void {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-danger';
        button.innerHTML = `${this.getTrashIcon()}`;
        button.setAttribute('data-action', 'click->collection-type#removeEntry');

        const fieldDiv = entry.querySelector('fieldset > div');
        if (null !== fieldDiv) {
            entry.querySelector('fieldset > div').outerHTML = `<div class="d-flex"><div class="flex-grow-1">${fieldDiv.outerHTML}</div><div class="align-self-start ms-3" style="margin-top: 2rem;">${button.outerHTML}</div></div>`;

            return;
        }

        const inputElement = entry.querySelector('select, input');
        const errorElements = entry.querySelectorAll('div.invalid-feedback');
        let errors = '';
        if (0 !== errorElements.length) {
            errorElements.forEach(e => { errors += e.outerHTML; });
            entry.querySelectorAll('div.invalid-feedback').forEach(e => e.remove());
        }

        entry.querySelector('select, input').outerHTML = `<div class="d-flex"><div class="flex-grow-1">${inputElement.outerHTML}${errors}</div><div class="align-self-start ms-3">${button.outerHTML}</div></div>`;
    }

    private processEntryLabel (entry: HTMLElement): HTMLElement {
        const legend = entry.querySelector('legend');

        if (null !== legend && false === this.hasLabelValue) {
            legend.remove();
        }

        if (RegExp(/^\d+$/u, 'u').exec(legend?.innerText)) {
            legend.innerText = `${this.labelValue} ${parseInt(legend.innerText, 10) + 1}`;
        }

        return entry;
    }

    private getEntryContent (): HTMLElement {
        const prototypeName: string = this.hasPrototypeNameValue ? this.prototypeNameValue : '__name__';
        const labelRegex: RegExp = new RegExp(`${prototypeName}label__`, 'gu');
        const nameRegex: RegExp = new RegExp(prototypeName, 'gu');

        if (!(this.element instanceof HTMLElement)) {
            throw new Error('The element should be an HTMLElement.');
        }

        let prototype = this.element.dataset.prototype;
        prototype = prototype
            .replace(labelRegex, `${this.labelValue ?? ''} ${this.index + 1}`)
            .replace(nameRegex, this.index.toString())
        ;
        prototype = prototype.trim();

        const template = document.createElement('template');
        template.innerHTML = prototype;

        return template.content.firstChild as HTMLElement;
    }

    private getPlusIcon (): string {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 16 16"><path fill="currentColor" fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2"/></svg>';
    }

    private getTrashIcon (): string {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 16 16"><path fill="currentColor" d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5M8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5m3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0"/></svg>';
    }
}
