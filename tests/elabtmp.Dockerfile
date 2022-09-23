# syntax=docker/dockerfile:1.3
# Dockerfile for CI image
FROM elabftw/elabimg:hypernext

# Set versions of used tools
ARG PHPSTAN_VERSION=1.8.5
ARG PSALM_VERSION=4.27.0
ARG PHAN_VERSION=5.4.1

# phpStan
ADD --chmod=755 https://github.com/phpstan/phpstan/releases/download/$PHPSTAN_VERSION/phpstan.phar /usr/bin/phpstan
# allow tmpfile, used by phpstan
RUN sed -i 's/tmpfile, //' /etc/php81/php.ini

# Psalm
ADD --chmod=755 https://github.com/vimeo/psalm/releases/download/$PSALM_VERSION/psalm.phar /usr/bin/psalm

# Phan
ADD --chmod=755 https://github.com/phan/phan/releases/download/$PHAN_VERSION/phan.phar /usr/bin/phan

# extend open_basedir
# /usr/bin/psalm, //autoload.php, /root/.cache/ are for psalm
# /usr/bin/phpstan, /proc/cpuinfo is for phpstan, https://github.com/phpstan/phpstan/issues/4427 https://github.com/phpstan/phpstan/issues/2965
RUN sed -i 's|^open_basedir*|&:/usr/bin/psalm://autoload\.php:/root/\.cache/:/usr/bin/phpstan:/proc/cpuinfo|' /etc/php81/php.ini
# Install xdebug for coverage
RUN apk add --update php81-pecl-xdebug
RUN printf "zend_extension=xdebug.so\nxdebug.mode=coverage" > /etc/php81/conf.d/42_xdebug.ini
