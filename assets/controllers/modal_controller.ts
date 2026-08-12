import { Controller } from '@hotwired/stimulus';

/** @class HTMLElement */
export default class extends Controller {
    static readonly targets = [
        'dialog',
        'frame',
        'skeleton'
    ];

    declare readonly dialogTarget: HTMLDialogElement;
    declare readonly frameTarget: HTMLElement;
    declare readonly skeletonTarget: HTMLTemplateElement;

    private loadingTimer: number | undefined;

    private observer: MutationObserver | undefined;

    connect () {
        // The frame is the state: content arriving opens the dialog, content leaving closes it.
        this.observer = new MutationObserver(() => {
            this.syncWithFrame();
        });

        this.observer.observe(this.frameTarget, {
            characterData: true,
            childList: true,
            subtree: true
        });
    }

    disconnect () {
        this.observer?.disconnect();
        this.observer = undefined;

        this.close();
    }

    // Also the dialog's own `close` listener (Esc, the method="dialog" buttons): every branch is
    // guarded, so the native close it reacts to cannot loop back through dialog.close().
    close () {
        this.clearLoadingTimer();

        if (this.dialogTarget.open) {
            this.dialogTarget.close();
        }

        if (0 < this.frameTarget.innerHTML.length) {
            this.frameTarget.innerHTML = '';
        }

        this.frameTarget.removeAttribute('src');

        document.documentElement.style.removeProperty('--app-scrollbar-width');
    }

    clickOutside (event: MouseEvent) {
        // The dialog carries no padding of its own, so a click landing on the element itself can
        // only be the backdrop.
        if (this.dialogTarget === event.target) {
            this.close();
        }
    }

    // On `turbo:before-fetch-request` at the document: the frame's own fetch (a link opening the
    // modal) bubbles here, a form declaring `data-turbo-frame="modal"` fires here; every other
    // Turbo request is ignored. Submissions from an already open modal pass the filter too and
    // are dropped by the `open` guard below — their pending state is the form, not a skeleton.
    showLoading (event: Event) {
        const { target } = event;
        const formToModal = target instanceof HTMLFormElement && 'modal' === target.dataset.turboFrame;

        if (this.frameTarget !== target && false === formToModal) {
            return;
        }

        this.clearLoadingTimer();
        this.loadingTimer = window.setTimeout(() => {
            this.loadingTimer = undefined;

            if (false === this.dialogTarget.open) {
                this.frameTarget.innerHTML = this.skeletonTarget.innerHTML;
            }
        }, 200);
    }

    // The lock in components/_modal.scss takes the scrollbar with it, and this is the width it has to
    // give back. Measured before the dialog opens, so a page that does not scroll answers 0 and gets no
    // strip at all — which is the whole reason this is not a gutter reserved once and for all.
    private reserveScrollbarWidth () {
        const width = Math.max(0, window.innerWidth - document.documentElement.clientWidth);

        document.documentElement.style.setProperty('--app-scrollbar-width', `${width}px`);
    }

    // clearTimeout swallows undefined, so no guard.
    private clearLoadingTimer () {
        window.clearTimeout(this.loadingTimer);
        this.loadingTimer = undefined;
    }

    private syncWithFrame () {
        const hasContent = 0 < this.frameTarget.innerHTML.trim().length;

        if (hasContent && false === this.dialogTarget.open) {
            this.reserveScrollbarWidth();
            this.dialogTarget.showModal();
        } else if (false === hasContent && this.dialogTarget.open) {
            this.close();
        }
    }
}
