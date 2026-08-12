#!/usr/bin/env node

import { Command } from 'commander';
import path from 'path';
import puppeteer from 'puppeteer';

// `--disable-dev-shm-usage` avoids Chromium crashes on hosts/containers with a small /dev/shm.
const LAUNCH_OPTIONS = { args: ['--no-sandbox', '--disable-dev-shm-usage'], headless: 'shell' };
const NAVIGATION_TIMEOUT = 30000;

/**
 * @param {string} urlString
 * @returns {boolean}
 */
const isValidUrl = function (urlString) {
    try {
        return Boolean(new URL(urlString));
    } catch {
        return false;
    }
}

/**
 * @param {string} url
 * @returns {string}
 */
const normalizeUrl = function (url) {
    let normalizedUrl = url;

    if (false === isValidUrl(url)) {
        normalizedUrl = `file://${path.resolve(url)}`;
    }

    return normalizedUrl;
}

const program = new Command();
program
    .name('html-print')
    .description('Print rendered HTML to PDF or image.')
    .version('0.1.0');

program.command('pdf')
    .description('Create a PDF from HTML')
    .argument('<source>', 'The HTML source.')
    .argument('<destination>', 'The file path to save PDF.')
    .option('--headerTemplate <template>',
        'HTML template for the print header.\n' +
        'Should be valid HTML with the following classes used to inject values into them:\n' +
        '- date: formatted print date\n' +
        '- title: document title\n' +
        '- url: document location\n' +
        '- pageNumber: current page number\n' +
        '- totalPages: total pages in the document.'
    )
    .option('--footerTemplate <template>',
        'HTML template for the print footer.\n' +
        'Should be valid HTML with the following classes used to inject values into them:\n' +
        '- date: formatted print date\n' +
        '- title: document title\n' +
        '- url: document location\n' +
        '- pageNumber: current page number\n' +
        '- totalPages: total pages in the document.'
    )
    .action(async (source, destination, options) => {
        const normalizedSource = normalizeUrl(source);

        const browser = await puppeteer.launch(LAUNCH_OPTIONS);

        try {
            const page = await browser.newPage();

            await page.goto(normalizedSource, { timeout: NAVIGATION_TIMEOUT });
            // The page size is the document's own business: without `preferCSSPageSize`, Puppeteer's
            // default (US Letter) wins over the `@page { size: ... }` the source declares.
            await page.pdf({
                displayHeaderFooter: undefined !== options.headerTemplate || undefined !== options.footerTemplate,
                footerTemplate: options.footerTemplate,
                headerTemplate: options.headerTemplate,
                path: destination,
                preferCSSPageSize: true,
                printBackground: true,
            });
        } finally {
            await browser.close();
        }
    });

program.command('screenshot')
    .description('Create a screenshot from HTML')
    .argument('<source>', 'The HTML source.')
    .argument('<destination>', 'The file path to save image.')
    .action(async (source, destination) => {
        const normalizedSource = normalizeUrl(source);

        const browser = await puppeteer.launch(LAUNCH_OPTIONS);

        try {
            const page = await browser.newPage();

            await page.goto(normalizedSource, { timeout: NAVIGATION_TIMEOUT });
            await page.setViewport({ height: 1080, width: 1920 });
            await page.screenshot({
                path: destination
            });
        } finally {
            await browser.close();
        }
    });

program.parse();
