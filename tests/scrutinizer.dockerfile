# syntax=docker/dockerfile:1.3
# Dockerfile for CI image
FROM elabftw/elabimg:hypernext

# Set versions of used tools
ARG PHPSTAN_VERSION=1.4.9
ARG PSALM_VERSION=4.22.0
ARG PHAN_VERSION=5.3.2

# phpStan
ADD --chmod=755 https://github.com/phpstan/phpstan/releases/download/$PHPSTAN_VERSION/phpstan.phar /usr/bin/phpstan

# Psalm
ADD --chmod=755 https://github.com/vimeo/psalm/releases/download/$PSALM_VERSION/psalm.phar /usr/bin/psalm

# Phan
ADD --chmod=755 https://github.com/phan/phan/releases/download/$PHAN_VERSION/phan.phar /usr/bin/phan

# Install xdebug for coverage
RUN apk add --update php81-pecl-xdebug
RUN printf "zend_extension=xdebug.so\nxdebug.mode=coverage" > /etc/php81/conf.d/42_xdebug.ini
