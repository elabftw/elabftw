#!/usr/bin/env bash
# this script exists to make marked great again by fixing the "main" key of the package.json
# provide the path to the package.json as first arg
set -eu

sed -ie "s#\"main\": \"./lib/marked.cjs#\"main\": \"./lib/marked.esm.js#" "$1"
