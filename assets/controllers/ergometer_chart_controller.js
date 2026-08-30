import { Controller } from '@hotwired/stimulus';

/**
 * The three charts of a phase stack up and only the last one draws the time axis, so they have to
 * start at the same x — otherwise that axis belongs to none of the traces above it. Left alone each
 * one sizes its y axis to its own widest tick, and « 2:30 », « 40 » and « 175 » are not the same
 * width: the plot areas came out 315, 322 and 325 px from the left at 1440.
 *
 * Wide enough for the widest tick any of them writes — a five-glyph pace like « 10:00 » — since the
 * axis is clipped to this and not scrolled.
 */
const Y_AXIS_WIDTH = 46;

/**
 * @param {number} seconds
 * @returns {string}
 */
const formatPace = function (seconds) {
    const formattedMinutes = Math.floor(seconds / 60);
    const formattedSeconds = Math.round(seconds % 60).toString().padStart(2, '0');

    return `${formattedMinutes}:${formattedSeconds}`;
}

export default class extends Controller {
    static values = { unit: String }

    connect () {
        this._onPreConnect = this._onPreConnect.bind(this);
        this.element.addEventListener('chartjs:pre-connect', this._onPreConnect);
    }

    disconnect () {
        this.element.removeEventListener('chartjs:pre-connect', this._onPreConnect);
    }

    _onPreConnect (event) {
        // The average is in the second dataset, do not display it
        event.detail.options.plugins.tooltip.filter = (item) => 0 === item.datasetIndex;

        // Before the early return: every chart is pinned, or the ones that are not drift again.
        event.detail.options.scales.y.afterFit = (scale) => {
            scale.width = Y_AXIS_WIDTH;
        };

        if ('pace' !== this.unitValue) {
            return;
        }

        event.detail.options.scales.y.ticks.callback = formatPace;
        event.detail.options.plugins.tooltip.callbacks = {
            label: (context) => formatPace(context.raw),
        };
    }
}
