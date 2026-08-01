import {join} from 'path';

/**
 * @type {import("puppeteer").Configuration}
 */
export default {
    cacheDirectory: join(import.meta.dirname, '.cache', 'puppeteer'),
    chrome: {
        skipDownload: true,
    },
    'chrome-headless-shell': {
        skipDownload: false,
    }
};
