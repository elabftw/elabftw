#!/bin/sh
# License: AGPLv3
# © 2020 Nicolas CARPi
# https://www.deltablot.com/elabftw

# the nginx server can be in http or https
protocol=http
if [ "$DISABLE_HTTPS" = false ]; then
    protocol=https
fi

# special endpoint healthcheck will reply 204 if nginx is up
status=$(curl -sk -o /dev/null -w "%{http_code}" ${protocol}://localhost:443/healthcheck)
if [ "$status" = "204" ]; then
    exit 0
fi
exit 1
