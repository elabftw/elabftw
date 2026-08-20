#!/bin/sh
# License: AGPLv3
# © 2020 Nicolas CARPi
# https://www.deltablot.com/elabftw

# HTTPS is enabled by default unless DISABLE_HTTPS=true
protocol='https'
if [ "${DISABLE_HTTPS:-false}" = 'true' ]; then
    protocol='http'
fi

# special endpoint healthcheck will reply 204 if nginx is up
wget --quiet --spider --no-check-certificate ${protocol}://127.0.0.1:8080/healthcheck
