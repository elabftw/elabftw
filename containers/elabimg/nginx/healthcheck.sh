#!/bin/sh
# License: AGPLv3
# © 2020 Nicolas CARPi
# https://www.deltablot.com/elabftw

isTrue() {
    local value="${1:-}"
    case "${value,,}" in
        true|yes|on|1) return 0 ;;
        *) return 1 ;;
    esac
}

# HTTPS is enabled by default unless DISABLE_HTTPS=true
protocol='https'
if isTrue "$DISABLE_HTTPS"; then
    protocol='http'
fi

# special endpoint healthcheck will reply 204 if nginx is up
wget --quiet --spider --no-check-certificate ${protocol}://127.0.0.1:8080/healthcheck
