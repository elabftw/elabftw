# syntax=docker/dockerfile:1.3
# Dockerfile for Cypress
FROM cypress/included:12.17.2

WORKDIR /e2e
RUN mkdir -p tests/cypress

RUN npm install typescript

# copy everything because we can't bind mount
COPY ../cypress.config.ts .
COPY ../tests/cypress ./tests/cypress

# overwrite default 'cypress run' entry point, will call it manually later
# https://github.com/cypress-io/cypress-docker-images/tree/master/included#keep-the-container
ENTRYPOINT ["tail", "-f", "/dev/null"]
