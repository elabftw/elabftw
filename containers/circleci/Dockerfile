# syntax=docker/dockerfile:1.3
# Dockerfile for CircleCI
FROM elabtmp

# add tests related folders and files to the image
COPY ./tests /elabftw/tests
COPY ./eslint.config.mjs /elabftw
COPY ./.php-cs-fixer.dist.php /elabftw
COPY ./.stylelintrc.json /elabftw
COPY ./codeception.yml /elabftw
COPY ./cypress.config.ts /elabftw

# install phpDocumentor
# https://github.com/phpDocumentor/phpDocumentor/releases/
ARG PHP_DOCUMENTOR_VERSION=v3.7.1
ADD --chmod=755 https://github.com/phpDocumentor/phpDocumentor/releases/download/$PHP_DOCUMENTOR_VERSION/phpDocumentor.phar phpdoc

# phpDocumentor requires ext-iconv and plantuml, graphviz for generating the svg graph
RUN apk add --update --no-cache plantuml graphviz php84-iconv
