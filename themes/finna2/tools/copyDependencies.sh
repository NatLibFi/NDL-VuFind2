#!/bin/sh

set -e

rm -r 'js/vendor/tify' || true
mkdir -p 'js/vendor/tify'

echo "Copying dependencies..."

cp --recursive -d --target-directory js/vendor/tify/ node_modules/tify/dist/*

echo "Done copying dependencies."
