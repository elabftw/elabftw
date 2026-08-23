#!/usr/bin/env bash
set -eu

WORKER_UID=$(id -u) WORKER_GID=$(id -g) docker compose -f containers/elabdev/docker-compose.yml build
WORKER_UID=$(id -u) WORKER_GID=$(id -g) docker compose -f containers/elabdev/docker-compose.yml up -d --wait --wait-timeout 64
docker exec elabftw yarn install
docker exec elabftw composer install
docker exec elabftw yarn watchjs
