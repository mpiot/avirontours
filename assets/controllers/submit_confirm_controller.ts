import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
/** @class HTMLFormElement */
export default class extends Controller {
    static readonly targets = [
        'content'
    ];

    declare readonly contentTarget: HTMLTemplateElement;

    onSubmit (event: SubmitEvent) {
        const modal = document.getElementById('modal');
        if (null === modal) {
            return;
        }

        // A submitter inside the modal is the confirmation itself, so it goes through. A submit with
        // no submitter at all cannot be that, and is confirmed like any other.
        const { submitter } = event;
        if (null !== submitter && modal.contains(submitter)) {
            return;
        }

        event.preventDefault();
        modal.innerHTML = this.contentTarget.innerHTML;
    }
}
