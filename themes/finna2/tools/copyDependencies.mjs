import { cp, mkdir, rm } from 'node:fs/promises';

console.log('Erasing old dependencies...');

await rm('js/vendor/UV/', {recursive: true});
await mkdir('js/vendor/UV/');

console.log('Copying dependencies...');

await cp('node_modules/universalviewer/dist/umd/.',
  'js/vendor/UV/', {recursive: true});

console.log('Done copying dependencies.');
