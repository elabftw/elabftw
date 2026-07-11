# syntax=docker/dockerfile:1.3
# Dockerfile for CI image
FROM elabftw/elabimg:ci

# Install xdebug for coverage
RUN apk add --update php84-pecl-xdebug
RUN printf "zend_extension=xdebug.so\nxdebug.mode=coverage" > /etc/php84/conf.d/50_xdebug.ini

# this yarn install is here because when running test suite we don't necessarily install js first in container elabtmp
RUN yarn install
