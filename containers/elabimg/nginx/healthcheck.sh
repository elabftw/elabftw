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
status=$(curl -sk -o /dev/null -w "%{http_code}" ${protocol}://localhost:8080/healthcheck)
if [ "$status" = "204" ]; then
    exit 0
fi
exit 1
