#!/usr/bin/env bash
# execute this from project's root
WORKER_UID=$(id -u) WORKER_GID=$(id -g) docker compose -f containers/elabdev/docker-compose.yml down
