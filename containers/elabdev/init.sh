#!/usr/bin/env bash
# run this script only once on a fresh dev env
set -eu

mkdir -pv ~/.local/share/elabdev/{exports,uploads,mysql,certs}
mkdir -pv ~/.cache/elabdev
mkcert -install
(cd "${HOME}/.local/share/elabdev/certs" && mkcert elab.localhost)
WORKER_UID=$(id -u) WORKER_GID=$(id -g) docker compose -f containers/elabdev/docker-compose.yml build
WORKER_UID=$(id -u) WORKER_GID=$(id -g) docker compose -f containers/elabdev/docker-compose.yml up -d --wait --wait-timeout 64
docker exec elabftw yarn install
docker exec elabftw yarn buildall:dev
docker exec elabftw composer install
docker exec -t elabftw bin/init db:populate -y src/tools/populate-config.yml.dist
echo "−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−"
echo "elabftw dev instance running at: https://elab.localhost:3148"
echo "login with toto@yopmail.com and password: totototototo"
echo "phpmyadmin is running on http://localhost:8082"
echo "swagger is running on http://localhost:8085"
echo "−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−−"
