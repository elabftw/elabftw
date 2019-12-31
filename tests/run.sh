#! /bin/sh
# https://www.elabftw.net
# tests/run.sh - run elabftw acceptance + unit tests with codeception

# stop on failure
set -eu

# kill chromedriver on exit
cleanup() {
    docker-compose -f tests/docker-compose.yml down
    sudo cp config.php.dev config.php
    sudo chown 100:101 config.php
}
trap cleanup EXIT

# swap config file for docker with the one for localhost
# sudo is needed because config file for docker is owned by 100:101
sudo cp config.php config.php.dev
sudo cp tests/config-home.php config.php
sudo chmod +r config.php
# launch a fresh environment
docker-compose -f tests/docker-compose.yml up -d
# give some time for the mysql process to start
echo "Waiting for MySQL to start..."
sleep 10
# install the database
docker exec -it elabtmp bin/install start
# populate the database
docker exec -it elabtmp bin/console dev:populate -p phpunitftw -m phpunit@example.com -u phpunit -s phpunit -y
# run tests
docker exec -it elabtmp php vendor/bin/codecept run --skip functionnal
