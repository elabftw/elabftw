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

# install phpdocumentor
RUN curl -sSL https://phpdoc.org/phpDocumentor.phar -o phpdoc && chmod +x phpdoc
# install plantuml for generating the svg graph
RUN apk add --update --no-cache plantuml
