#!/usr/bin/env node

import { Command } from 'commander';
import puppeteer from 'puppeteer';
import path from 'path';

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
        source = normalizeUrl(source);

        const browser = await puppeteer.launch({ headless: 'shell', args: ['--no-sandbox'] });
        const page = await browser.newPage();

        await page.goto(source);
        await page.pdf({
            displayHeaderFooter: undefined !== options.headerTemplate || undefined !== options.footerTemplate,
            headerTemplate: options.headerTemplate,
            footerTemplate: options.footerTemplate,
            printBackground: true,
            path: destination
        });

        await browser.close();
    });

program.command('screenshot')
    .description('Create a screenshot from HTML')
    .argument('<source>', 'The HTML source.')
    .argument('<destination>', 'The file path to save image.')
    .action(async (source, destination, options) => {
        source = normalizeUrl(source);

        const browser = await puppeteer.launch({ headless: 'shell', args: ['--no-sandbox'] });
        const page = await browser.newPage();

        await page.goto(source);
        await page.screenshot({
            path: destination
        });

        await browser.close();
    });

program.parse();

/**
 * @param {string} urlString
 * @returns {boolean}
 */
function isValidUrl (urlString) {
    try {
        return Boolean(new URL(urlString));
    } catch (exception) {
        return false;
    }
}

/**
 * @param {string} url
 * @returns {string}
 */
function normalizeUrl (url) {
    if (false === isValidUrl(url)) {
        url = `file://${path.resolve(url)}`;
    }

    return url;
}
