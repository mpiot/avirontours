#!/usr/bin/env node

import { Command } from 'commander';
import path from 'path';
import puppeteer from 'puppeteer';

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

        const browser = await puppeteer.launch({ args: ['--no-sandbox'], headless: 'shell' });
        const page = await browser.newPage();

        await page.goto(normalizedSource);
        await page.pdf({
            displayHeaderFooter: undefined !== options.headerTemplate || undefined !== options.footerTemplate,
            footerTemplate: options.footerTemplate,
            headerTemplate: options.headerTemplate,
            landscape: true,
            path: destination,
            printBackground: true,
        });

        await browser.close();
    });

program.command('screenshot')
    .description('Create a screenshot from HTML')
    .argument('<source>', 'The HTML source.')
    .argument('<destination>', 'The file path to save image.')
    .action(async (source, destination) => {
        const normalizedSource = normalizeUrl(source);

        const browser = await puppeteer.launch({ args: ['--no-sandbox'], headless: 'shell' });
        const page = await browser.newPage();

        await page.goto(normalizedSource);
        await page.setViewport({ height: 1080, width: 1920 });
        await page.screenshot({
            path: destination
        });

        await browser.close();
    });

program.parse();
