import { Controller } from '@hotwired/stimulus';

/** @class HTMLElement */
export default class extends Controller {
    static readonly targets = [
        'addEntryButton',
        'counter',
        'entry',
    ];

    static readonly values = {
        autoFocus: { default: true, type: Boolean},
        buttonId: String,
        buttonText: String,
        displayCounter: { default: false, type: Boolean },
        label: String,
        numberEntriesAtInit: { default: 1, type: Number },
        prototypeName: String,
        removeLastEmptyEntry: { default: false, type: Boolean },
    };

    declare readonly addEntryButtonTarget: HTMLButtonElement;
    declare readonly counterTarget: HTMLSpanElement;
    declare readonly entryTargets: (HTMLDivElement|HTMLFieldSetElement)[];
    declare readonly hasAddEntryButtonTarget: boolean;

    declare readonly autoFocusValue: boolean;
    declare readonly buttonIdValue: string;
    declare readonly buttonTextValue: string;
    declare readonly displayCounterValue: boolean;
    declare readonly hasButtonIdValue: boolean;
    declare readonly hasButtonTextValue: boolean;
    declare readonly hasLabelValue: boolean;
    declare readonly hasPrototypeNameValue: boolean;
    declare readonly labelValue: string;
    declare readonly numberEntriesAtInitValue: number;
    declare readonly prototypeNameValue: string;
    declare readonly removeLastEmptyEntryValue: boolean;

    private index: number = 0;

    connect () {
        // Insert an add entry button
        this.appendAddEntryButton();

        // Process existing entries
        this.processExistingEntries();

        // Initialize the index
        this.initIndex();

        // Create default field(s) if needed
        const fieldCount = this.entryTargets.length;
        if (fieldCount < this.numberEntriesAtInitValue) {
            for (let i = fieldCount; i < this.numberEntriesAtInitValue; ++i) {
                this.addEntry(null);
            }
        }

        // Add submit listener: remove last entry if first input is empty
        if (true === this.removeLastEmptyEntryValue) {
            const form = this.element.closest('form');
            form.addEventListener('submit', () => {
                const entries = this.entryTargets;
                const lastEntry = entries.at(-1);
                const firstField = lastEntry.querySelector('input');

                if (2 > entries.length) {
                    return;
                }

                if (null === firstField || undefined === firstField) {
                    return;
                }

                if ('' === firstField.value) {
                    this.removeChildElement(lastEntry);
                }
            });
        }
    }

    addEntry (event: Event|null): void {
        if (null !== event) {
            event.preventDefault();
        }

        // Get the entry content
        const entry = this.getEntryPrototype();
        this.processEntry(entry);

        // Insert the new entry in DOM
        this.element.insertBefore(entry, this.addEntryButtonTarget);

        // Update the index
        this.index++;

        // Autofocus
        this.autofocus();
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
        const removeButton: HTMLButtonElement = event.currentTarget as HTMLButtonElement;
        const entry: HTMLDivElement|HTMLFieldSetElement = removeButton.closest('[data-collection-type-target="entry"]');

        this.removeChildElement(entry);
    }

    private appendAddEntryButton (): void {
        if (true === this.hasAddEntryButtonTarget) {
            return;
        }

        const icon = '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 16 16"><path fill="currentColor" fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2"/></svg>';
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-outline-secondary btn-sm align-self-end position-relative';
        button.innerHTML = `${icon} ${this.hasButtonTextValue ? this.buttonTextValue : 'Ajouter'}`;
        button.dataset.collectionTypeTarget = 'addEntryButton';
        button.dataset.action = 'click->collection-type#addEntry';

        // If we define a specific id for the button
        if (this.hasButtonIdValue) {
            button.id = this.buttonIdValue;
        }

        this.element.append(button);

        // Add the counter in the button (default: hidden)
        const span = document.createElement('span');
        span.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-primary';
        span.dataset.collectionTypeTarget = 'counter';

        if (false === this.displayCounterValue) {
            span.classList.add('d-none');
        }

        this.addEntryButtonTarget.appendChild(span);
    }

    private getEntryPrototype (): HTMLDivElement|HTMLFieldSetElement {
        const prototypeName = this.getPrototypeName();
        const labelRegex: RegExp = new RegExp(`${prototypeName}label__`, 'gu');
        const nameRegex: RegExp = new RegExp(prototypeName, 'gu');

        let prototype = this.getPrototypeSource();
        prototype = prototype
            .replace(labelRegex, `${this.labelValue ?? ''} ${this.index + 1}`)
            .replace(nameRegex, this.index.toString())
        ;
        prototype = prototype.trim();

        const template = document.createElement('template');
        template.innerHTML = prototype;

        return template.content.firstChild as HTMLDivElement|HTMLFieldSetElement;
    }

    private getPrototypeSource(): string {
        if (!(this.element instanceof HTMLElement)) {
            throw new TypeError('The element should be an HTMLElement.');
        }

        return this.element.dataset.prototype;
    }

    private getPrototypeName(): string {
        return this.hasPrototypeNameValue ? this.prototypeNameValue : '__name__';
    }

    private initIndex(): void {
        const lastEntry = this.entryTargets.at(-1);
        if (undefined === lastEntry) {
            return;
        }

        const regex = new RegExp(`id="(?<id>[a-zA-Z0-9_]+_)${this.getPrototypeName()}"`, 'u');
        const matchArray = regex.exec(this.getPrototypeSource());
        const id = matchArray.groups.id;
        const lastElement = lastEntry.querySelector(`[id^=${id}]`);
        if (null === lastElement) {
            this.index = this.entryTargets.length;

            return;
        }

        const lastElementId = lastElement.id;
        this.index = Number.parseInt(lastElementId.split('_').pop(), 10) + 1;
    }

    private processEntry(entry: HTMLDivElement|HTMLFieldSetElement): void
    {
        // If the entry is already processed: return
        if (this.entryTargets.includes(entry)) {
            return;
        }

        this.processEntryLabel(entry);
        this.processEntryRemoveButton(entry);
        entry.dataset.collectionTypeTarget = 'entry';
    }

    private processEntryLabel (entry: HTMLDivElement|HTMLFieldSetElement): void {
        const legend = entry.querySelector('legend');
        if (null !== legend && false === this.hasLabelValue) {
            legend.remove();
        }

        const regExp = new RegExp(/^\d+$/u, 'u');
        if (regExp.exec(legend?.innerText)) {
            legend.innerText = `${this.labelValue} ${Number.parseInt(legend.innerText, 10) + 1}`;
        }
    }

    private processEntryRemoveButton (entry: HTMLDivElement|HTMLFieldSetElement): void {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-danger';
        button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 16 16"><path fill="currentColor" d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5M8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5m3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0"/></svg>';
        button.dataset.action = 'click->collection-type#removeEntry';

        const container = entry.querySelector('fieldset > div, div > div');
        const style = 'FIELDSET' === entry.tagName ? 'margin-top: 2rem;' : '';
        if (null !== container) {
            container.outerHTML = `
                <div class="d-flex">
                    <div class="flex-grow-1">
                        ${container.outerHTML}
                    </div>
                    <div class="align-self-start ms-3" style="${style}">
                        ${button.outerHTML}
                    </div>
                </div>
           `;

            return;
        }

        console.error('The prototype cannot be handle.');
    }

    private processExistingEntries (): void {
        const childNodes = this.element.childNodes;
        const entries = [...childNodes].filter((el: HTMLElement) => this.addEntryButtonTarget !== el) as (HTMLDivElement|HTMLFieldSetElement)[];
        for (const entry of entries) {
            this.processEntry(entry);
        }

        // Autofocus
        this.autofocus();
    }

    private removeChildElement(entry: HTMLDivElement|HTMLFieldSetElement): void
    {
        entry.remove();
    }

    // Target events: update the counter
    private entryTargetConnected (): void
    {
        this.counterTarget.innerHTML = this.entryTargets.length.toString();
    }

    private entryTargetDisconnected (): void
    {
        this.counterTarget.innerHTML = this.entryTargets.length.toString();
    }

    private autofocus (): void
    {
        if (false === this.autoFocusValue) {
            return;
        }

        if (0 === this.entryTargets.length) {
            return;
        }

        const lastEntry = this.entryTargets.at(-1);
        const field: HTMLInputElement = lastEntry.querySelector('input[type="text"][required]:not([value])')
        if (null === field) {
            return;
        }

        field.focus();
    }
}
