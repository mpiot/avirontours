import { Controller } from '@hotwired/stimulus';

const read = (value: string): string => {
    const duration = value.trim();
    if (!(/^\d+$/u).test(duration)) {
        return '';
    }

    const minutes = Number(duration);
    if (60 > minutes) {
        return `→ ${minutes} min`;
    }

    return `→ ${Math.floor(minutes / 60)} h ${String(minutes % 60).padStart(2, '0')}`;
};

/* stimulusFetch: 'lazy' */
/** @class HTMLElement */
export default class extends Controller {
    static readonly targets = [
        'input',
        'reading'
    ];

    declare readonly inputTarget: HTMLInputElement;
    declare readonly readingTarget: HTMLElement;

    connect () {
        this.render();
    }

    render () {
        this.readingTarget.textContent = read(this.inputTarget.value);
    }
}
