#! /bin/sh
set -eu

function cleanup {
    killall chromedriver
}

trap cleanup EXIT

mysql -uroot -e 'DROP DATABASE IF EXISTS phpunit; CREATE DATABASE phpunit;'
mysql -uroot phpunit < src/sql/structure.sql
chromedriver --url-base=/wd/hub &
php vendor/bin/codecept run --skip functionnal
