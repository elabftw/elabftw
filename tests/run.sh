#! /bin/sh
# https://www.elabftw.net
# tests/run.sh - run elabftw acceptance + unit tests with codeception

# stop on failure
set -eu

# kill chromedriver on exit
function cleanup {
    killall chromedriver
    sudo cp config.php.docker config.php
    sudo chown 100:101 config.php
}
trap cleanup EXIT

# swap config file for docker with the one for localhost
# sudo is needed because config file for docker is owned by 100:101
sudo cp config.php config.php.docker
sudo cp tests/config-home.php config.php
sudo chmod +r config.php
# start chromedriver
chromedriver --url-base=/wd/hub &
# create a fresh phpunit database
mysql -uroot -e 'DROP DATABASE IF EXISTS phpunit; CREATE DATABASE phpunit;'
# import the structure of the tables
mysql -uroot phpunit < src/sql/structure.sql
# run tests
php vendor/bin/codecept run --skip functionnal
