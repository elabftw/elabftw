#!/usr/bin/env bash

set -euo pipefail

if [ "$#" -ne 2 ]; then
    echo "Usage: $0 <csrf-token> <php-session-id>"
    exit 1
fi

CSRF="$1"
PHPSESSID="$2"

BASE_URL="${BASE_URL:-https://elab.local:3148}"
REQUESTS="${REQUESTS:-30}"
PARALLEL="${PARALLEL:-30}"

export CSRF PHPSESSID BASE_URL

seq "$REQUESTS" | xargs -P "$PARALLEL" -I '{}' sh -c '
    status=$(curl \
        --silent \
        --show-error \
        --insecure \
        --output /dev/null \
        --write-out "%{http_code}" \
        --request POST \
        --cookie "PHPSESSID=$PHPSESSID" \
        --data-urlencode "csrf=$CSRF" \
        --data-urlencode "auth_type=rate_limit_test" \
        "$BASE_URL/app/controllers/LoginController.php")

    printf "request %02d -> %s\n" "{}" "$status"
'
