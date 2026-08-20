#!/usr/bin/env bash
set -e

CYPRESS_IMG="elabftw/elab-cypress:15.20.0"
CONTAINER_UID="$(docker run --rm --entrypoint id "$CYPRESS_IMG" -u node)"
X11_ACL="SI:localuser:#${CONTAINER_UID}"
X11_AUTH_ADDED=0

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd -- "${SCRIPT_DIR}/../.." && pwd)"

cleanup() {
    if (( X11_AUTH_ADDED )); then
        xhost "-${X11_ACL}" >/dev/null
    fi
}

trap cleanup EXIT

if ! xhost | grep -Fqx "${X11_ACL}"; then
    xhost "+${X11_ACL}" >/dev/null
    X11_AUTH_ADDED=1
fi

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
