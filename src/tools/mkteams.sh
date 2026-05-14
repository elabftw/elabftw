#!/usr/bin/env bash
# use this script to generate many teams in local dev
set -euo pipefail

K='apiKey4Test'
N_TEAMS="${1:-120}"

for ((i = 1; i <= N_TEAMS; i++)); do
    t=$RANDOM
    echo "Creating team $t ($i/$N_TEAMS)"
    curl \
        -H "Authorization: $K" \
        -H 'Content-Type: application/json' \
        -k \
        -X POST \
        -d "{\"name\": \"$t\"}" \
        'https://elab.local:3148/api/v2/teams'
done
