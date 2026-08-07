import { Collapse } from 'bootstrap';
import { Controller } from '@hotwired/stimulus';

/** @class HTMLElement */
export default class extends Controller {
    static readonly targets = [
        'addButton',
        'badge',
        'guardian',
        'secondGuardian',
        'section',
        'toggle'
    ];

    static readonly values = {
        majorityDate: String,
        minor: Boolean
    };

    declare readonly addButtonTarget: HTMLButtonElement;
    declare readonly badgeTarget: HTMLElement;
    declare readonly guardianTargets: HTMLElement[];
    declare readonly majorityDateValue: string;
    declare readonly secondGuardianTarget: HTMLElement;
    declare readonly sectionTarget: HTMLElement;
    declare readonly toggleTarget: HTMLButtonElement;

    declare minorValue: boolean;

    connect () {
        this.refresh();
    }

    checkBirthday (event: Event) {
        const birthday: string = (event.currentTarget as HTMLInputElement).value;

        this.minorValue = '' !== birthday && birthday > this.majorityDateValue;
        this.refresh();

        // Correcting a birthday back to an adult one has to undo the auto-expansion,
        // otherwise the section stays open with fields that are all-or-nothing required.
        if (!this.minorValue && 0 === this.countFilled()) {
            this.collapse();
        }
    }

    clearFirst () {
        this.clear(this.guardianTargets[0]);
        this.refresh();
    }

    addSecond () {
        this.secondGuardianTarget.classList.remove('d-none');
        const field: HTMLInputElement|HTMLSelectElement = this.secondGuardianTarget.querySelector('input, select');
        if (null !== field) {
            field.focus();
        }

        this.addButtonTarget.classList.add('d-none');
    }

    removeSecond () {
        this.clear(this.secondGuardianTarget);
        this.secondGuardianTarget.classList.add('d-none');

        this.addButtonTarget.classList.remove('d-none');
        this.refresh();
    }

    refresh () {
        const count = this.countFilled();

        this.badgeTarget.textContent = `${count} renseigné${1 < count ? 's' : ''}`;
        this.badgeTarget.classList.toggle('d-none', 0 === count);

        if (this.minorValue || 0 < count) {
            this.expand();
        }
    }

    private countFilled (): number {
        return this.guardianTargets.filter(function (guardian: HTMLElement) {
            return Array
                .from(guardian.querySelectorAll('input, select'))
                .some(function (field: HTMLInputElement|HTMLSelectElement) {
                    return '' !== field.value;
                })
            ;
        }).length;
    }

    private clear (guardian: HTMLElement) {
        guardian
            .querySelectorAll('input, select')
            .forEach(function (field: HTMLInputElement|HTMLSelectElement) {
                field.value = '';
            })
        ;
    }

    private expand () {
        Collapse.getOrCreateInstance(this.sectionTarget, { toggle: false }).show();
    }

    private collapse () {
        Collapse.getOrCreateInstance(this.sectionTarget, { toggle: false }).hide();
    }
}
