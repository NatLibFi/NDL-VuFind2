#!/bin/sh

set -e

rm -rf 'js/vendor/tify' || true
mkdir -p 'js/vendor/tify'

echo "Copying dependencies..."

install -d js/vendor/tify js/vendor/tify/translations
install -m 0644 node_modules/tify/dist/tify.js  js/vendor/tify/tify.min.js
install -m 0644 node_modules/tify/dist/tify.css css/vendor/tify.min.css

find node_modules/tify/dist/translations -type f -exec install -m 0644 {} js/vendor/tify/translations \;

echo "Done copying dependencies."
