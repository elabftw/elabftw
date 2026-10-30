#!/bin/sh
# License: AGPLv3
# © 2020 Nicolas CARPi
# https://www.deltablot.com/elabftw

isTrue() {
    case "${1:-}" in
        [Tt][Rr][Uu][Ee]|[Yy][Ee][Ss]|[Oo][Nn]|1) return 0 ;;
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
