# syntax=docker/dockerfile:1.3
# Dockerfile for Cypress
FROM cypress/included:14.5.4

WORKDIR /home/node

# copy everything because we can't bind mount in CI
COPY cypress.config.ts .
COPY tests/cypress/ ./tests/cypress/
COPY tests/_data/ ./tests/_data/

# create cypress output folders
# use bash so we can expand the command (RUN will use sh)
RUN bash -c 'mkdir -p tests/cypress/{videos,screenshots}'
RUN chown -R node:node tests/cypress

USER node

# add html validation tools to cypress
RUN npm install typescript html-validate@^8 cypress-html-validate@^7 cypress-terminal-report

# overwrite default 'cypress run' entry point, will call it manually later
# https://github.com/cypress-io/cypress-docker-images/tree/master/included#keep-the-container
ENTRYPOINT ["tail", "-f", "/dev/null"]
