import * as Turbo from '@hotwired/turbo';

const TurboHelper = class {
    constructor () {
        document.addEventListener('turbo:before-cache', () => {
            TurboHelper.reEnableSubmitButtons();
        });

        document.addEventListener('turbo:submit-start', (event) => {
            const submitter = event.detail.formSubmission.submitter;
            if (undefined === submitter) {
                return;
            }

            submitter.toggleAttribute('disabled', true);
            submitter.classList.add('turbo-submit-disabled');
        });

        document.addEventListener('turbo:before-fetch-request', (event) => {
            TurboHelper.beforeFetchRequest(event);
        });

        document.addEventListener('turbo:before-fetch-response', (event) => {
            TurboHelper.beforeFetchResponse(event);
        });
    }

    static reEnableSubmitButtons () {
        document.querySelectorAll('.turbo-submit-disabled').forEach((button) => {
            button.toggleAttribute('disabled', false);
            button.classList.remove('turbo-submit-disabled');
        });
    }

    static beforeFetchRequest (event) {
        const frameId = event.detail.fetchOptions.headers['Turbo-Frame'];
        if (!frameId) {
            return;
        }

        const frame = document.getElementById(frameId);
        if (!frame || !frame.dataset.turboFormRedirect) {
            return;
        }

        event.detail.fetchOptions.headers['Turbo-Frame-Redirect'] = 1;
    }

    static beforeFetchResponse (event) {
        const fetchResponse = event.detail.fetchResponse;
        const redirectLocation = fetchResponse.response.headers.get('Turbo-Location');
        if (!redirectLocation) {
            return;
        }

        event.preventDefault();
        Turbo.cache.clear();
        Turbo.visit(redirectLocation);
    }
};

export default new TurboHelper();
