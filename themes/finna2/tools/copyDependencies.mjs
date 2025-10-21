import { copyFile } from 'node:fs/promises';

let buildDepsOnly = false;
process.argv.forEach(arg => {
    if (arg === '--only-build-deps') {
        buildDepsOnly = true;
    }
});

console.log('Copying dependencies...');

// Clover IIIF viewer
await copyFile('node_modules/@samvera/clover-iiif/dist/web-components/index.umd.js',
    'js/vendor/clover.umd.js');

console.log('Done copying dependencies.');
