# syntax=docker/dockerfile:1.3
# Dockerfile for CI image
FROM elabftw/elabimg:ci

# Install xdebug for coverage
RUN apk add --update php84-pecl-xdebug
RUN printf "zend_extension=xdebug.so\nxdebug.mode=coverage" > /etc/php84/conf.d/50_xdebug.ini
