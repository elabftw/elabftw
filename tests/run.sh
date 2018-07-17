#! /bin/sh
# https://www.elabftw.net
# tests/run.sh - run elabftw acceptance + unit tests with codeception

# stop on failure
set -eu

# kill chromedriver on exit
function cleanup {
    killall chromedriver
}
trap cleanup EXIT

# start chromedriver
chromedriver --url-base=/wd/hub &
# create a fresh phpunit database
mysql -uroot -e 'DROP DATABASE IF EXISTS phpunit; CREATE DATABASE phpunit;'
# import the structure of the tables
mysql -uroot phpunit < src/sql/structure.sql
# run tests
php vendor/bin/codecept run --skip functionnal
