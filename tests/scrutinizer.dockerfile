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

#Phan
ADD --chmod=755 https://github.com/phan/phan/releases/download/$PHAN_VERSION/phan.phar /usr/bin/phan

COPY ./bin /elabftw/bin
COPY ./src /elabftw/src
COPY ./tests /elabftw/tests
COPY ./web /elabftw/web
COPY ./.eslintrc.js /elabftw
COPY ./.php-cs-fixer.dist.php /elabftw
COPY ./.stylelintrc.json /elabftw
COPY ./builder.js /elabftw
COPY ./codeception.yml /elabftw
COPY ./composer.json /elabftw
COPY ./composer.lock /elabftw
COPY ./cypress.json /elabftw
COPY ./node-builder.js /elabftw
COPY ./package.json /elabftw
COPY ./yarn.lock /elabftw
