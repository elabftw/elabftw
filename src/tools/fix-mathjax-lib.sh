#!/usr/bin/env bash
# see https://github.com/elabftw/elabftw/issues/3593
set -eu

cat > "$1/js/components/version.js" << EOT
"use strict"
Object.defineProperty(exports, "__esModule", { value: true });
exports.VERSION = '3.2.1';
//# sourceMappingURL=version.js.map
EOT
