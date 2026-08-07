import * as Turbo from '@hotwired/turbo';

const TurboHelper = class {
    // Désactiver le bouton pendant l'envoi est déjà le comportement de Turbo
    // (config.forms.submitter vaut "disabled"), et Bootstrap grise déjà .btn:disabled.
    constructor () {
        document.addEventListener('turbo:before-fetch-request', (event) => {
            TurboHelper.beforeFetchRequest(event);
        });

        document.addEventListener('turbo:before-fetch-response', (event) => {
            TurboHelper.beforeFetchResponse(event);
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
