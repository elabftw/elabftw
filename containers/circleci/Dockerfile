# syntax=docker/dockerfile:1.3
# Dockerfile for CircleCI
FROM elabftw/elabtmp

# add tests related folders and files to the image
COPY ./tests /elabftw/tests
COPY ./eslint.config.mjs /elabftw
COPY ./.php-cs-fixer.dist.php /elabftw
COPY ./.stylelintrc.json /elabftw
COPY ./codeception.yml /elabftw
COPY ./cypress.config.ts /elabftw
