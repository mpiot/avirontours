import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.element.addEventListener('chartjs:pre-connect', this._onPreConnect);
    }

    disconnect() {
        this.element.removeEventListener('chartjs:pre-connect', this._onPreConnect);
    }

    _onPreConnect(event) {
        event.detail.options.scales.pace.ticks.callback = function(value) {
            const minutes = parseInt(value / 60);
            const seconds = parseInt(value % 60).toString().padEnd(2, '0');

            return `${minutes}:${seconds}`;
        };

        event.detail.options.plugins.tooltip.callbacks = {
            label: function(context) {
                if ('pace' === context.dataset.yAxisID) {
                    const minutes = parseInt(context.raw / 60);
                    const seconds = parseInt(context.raw % 60).toString().padEnd(2, '0');

                    return `${minutes}:${seconds}`;
                }

                return context.formattedValue;
            }
        };
    }
}
