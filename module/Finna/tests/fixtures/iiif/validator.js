#!/usr/bin/env node

/**
 * This script validates that a JSON string given in stdin is a valid IIIF
 * Presentation API document. It is run from
 * IIIFManifestGeneratorIntegrationTest.php.
 *
 * Usage:
 *   validator.js < foo.json
 *
 * Copyright (C) The National Library of Finland 2026.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @author Ronja Koistinen <ronja.koistinen@helsinki.fi>
 * @license GPL-2.0-only
 */

const { parseManifest } = require('manifesto.js/dist-commonjs');

async function readStdin() {
    return new Promise((resolve, reject) => {
        let buf = '';
        process.stdin.setEncoding('utf-8');
        process.stdin.on('data', chunk => buf += chunk);
        process.stdin.on('end', () => resolve(buf));
        process.stdin.on('error', err => reject(err));
    });
}

async function main() {
    try {
        const input = await readStdin();

        if (!input.trim()) {
            throw new Error('Empty input');
        }

        const presentation = JSON.parse(input);
        const result = parseManifest(presentation);
        if (result === null) {
            throw new Error(`Does not parse: ${input}`);
        }
    } catch (e) {
        console.error('Validation failed:', e.message);
        process.exit(1);
    }
    console.log('Validation passed');
    process.exit(0);
}

main();
