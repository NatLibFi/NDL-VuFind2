#!/bin/sh

set -e

rm -rf 'js/vendor/tify' || true
mkdir -p 'js/vendor/tify'

echo "Copying dependencies..."

install -d js/vendor/tify js/vendor/tify/translations
install -m 0644 node_modules/tify/dist/tify.js  js/vendor/tify/tify.min.js
install -m 0644 node_modules/tify/dist/tify.css css/vendor/tify.min.css

# Once the TIFY project has merged our pull requests and put out a new release
# containing our contributions, remove the 'i18n/tify/*' part here as well as
# our local copies of the files in /themes/finna2/i18n/tify
install -m 0644 node_modules/tify/dist/translations/* i18n/tify/* -t js/vendor/tify/translations

echo "Done copying dependencies."
