import * as Turbo from '@hotwired/turbo';

const STALE_OVERLAYS = '.offcanvas.show, .offcanvas-lg.show, .dropdown-menu.show';
const STALE_TRIGGERS = '[data-bs-toggle="dropdown"][aria-expanded="true"], [data-bs-toggle="offcanvas"][aria-expanded="true"]';

const TurboHelper = class {
    constructor () {
        document.addEventListener('turbo:before-fetch-request', (event) => {
            TurboHelper.beforeFetchRequest(event);
        });

        document.addEventListener('turbo:before-fetch-response', (event) => {
            TurboHelper.beforeFetchResponse(event);
        });

        document.addEventListener('turbo:before-cache', () => {
            TurboHelper.beforeCache();
        });
    }

    static beforeCache () {
        document.querySelectorAll('.offcanvas-backdrop').forEach((element) => {
            element.remove();
        });

        document.querySelectorAll(STALE_OVERLAYS).forEach((element) => {
            element.classList.remove('show');
        });

        document.querySelectorAll(STALE_TRIGGERS).forEach((element) => {
            element.setAttribute('aria-expanded', 'false');
        });

        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
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
