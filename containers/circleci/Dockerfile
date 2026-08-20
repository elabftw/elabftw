# syntax=docker/dockerfile:1.3
# Dockerfile for CircleCI
ARG ELABTMP_TAG=latest
FROM elabftw/elabtmp:${ELABTMP_TAG}

RUN composer install --prefer-dist --no-cache --no-progress
RUN yarn install --immutable
RUN yarn run buildall:ci

# add tests related folders and files to the image
COPY ./tests /elabftw/tests
COPY ./eslint.config.mjs /elabftw
COPY ./.php-cs-fixer.dist.php /elabftw
COPY ./.stylelintrc.json /elabftw
COPY ./codeception.yml /elabftw
COPY ./cypress.config.ts /elabftw
