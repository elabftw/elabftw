#!/usr/bin/env bash
set -eu

WORKER_UID=$(id -u) WORKER_GID=$(id -g) docker compose -f containers/elabdev/docker-compose.yml down
docker rmi elabftw-elabimg elabftw/elabdev:edge || true

sudo rm -rv -- "${HOME:?HOME must be set}/.local/share/elabdev"
rm -rv -- "${HOME:?HOME must be set}/.cache/elabdev"
