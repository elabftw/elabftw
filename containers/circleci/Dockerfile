# syntax=docker/dockerfile:1.3
# Dockerfile for CircleCI
ARG ELABTMP_TAG=latest
FROM elabftw/elabtmp:${ELABTMP_TAG}

# run yarn install again because we removed yarn stuff on elabimg build
RUN yarn install --immutable

# add tests related folders and files to the image
COPY ./tests /elabftw/tests
COPY ./eslint.config.mjs /elabftw
COPY ./.php-cs-fixer.dist.php /elabftw
COPY ./.stylelintrc.json /elabftw
COPY ./codeception.yml /elabftw
COPY ./cypress.config.ts /elabftw
