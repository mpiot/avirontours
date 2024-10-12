const {join} = require('path');

/**
 * @type {import("puppeteer").Configuration}
 */
module.exports = {
    cacheDirectory: join(__dirname, 'var', '.puppeteer.cache'),
    chrome: {
        skipDownload: true,
    },
    'chrome-headless-shell': {
        skipDownload: false,
    }
};
