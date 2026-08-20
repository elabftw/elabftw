#!/usr/bin/env bash
set -e

CYPRESS_IMG="elabftw/elab-cypress:15.20.0"
X11_USER="$(id -un)"
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd -- "${SCRIPT_DIR}/../.." && pwd)"

cleanup() {
    xhost "-SI:localuser:${X11_USER}" >/dev/null
}

trap cleanup EXIT
xhost "+SI:localuser:${X11_USER}" >/dev/null

docker run --rm -it \
    --user node \
    --add-host host.docker.internal:host-gateway \
    --env DISPLAY="$DISPLAY" \
    --env CYPRESS_BASE_URL=https://host.docker.internal:3148 \
    --volume /tmp/.X11-unix:/tmp/.X11-unix:ro \
    --volume "${PROJECT_DIR}":/home/node/e2e \
    --workdir /home/node/e2e \
    --entrypoint cypress \
    "$CYPRESS_IMG" \
    open --project /home/node/e2e
