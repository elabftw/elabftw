# syntax=docker/dockerfile:1.3
# Dockerfile for CircleCI
FROM elabtmp

# avoid carry-over from hypernext
RUN rm -rf /elabftw/*

# copy everything because we can't bind mount
COPY .. /elabftw
COPY ../bin /elabftw/bin
COPY ../src /elabftw/src
COPY ../tests /elabftw/tests
COPY ../web /elabftw/web
COPY ../.eslintrc.js /elabftw
COPY ../.php-cs-fixer.dist.php /elabftw
COPY ../.stylelintrc.json /elabftw
COPY ../builder.js /elabftw
COPY ../codeception.yml /elabftw
COPY ../composer.json /elabftw
COPY ../composer.lock /elabftw
COPY ../cypress.config.ts /elabftw
COPY ../node-builder.js /elabftw
COPY ../package.json /elabftw
COPY ../yarn.lock /elabftw

# install phpDocumentor
ARG PHP_DOCUMENTOR_VERSION=v3.3.1
ADD --chmod=755 https://github.com/phpDocumentor/phpDocumentor/releases/download/$PHP_DOCUMENTOR_VERSION/phpDocumentor.phar phpdoc

# phpDocumentor requires ext-iconv and plantuml, graphviz for generating the svg graph
RUN apk add --update --no-cache plantuml graphviz php83-iconv
