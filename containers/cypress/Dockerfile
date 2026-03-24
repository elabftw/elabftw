# syntax=docker/dockerfile:1.3
# Dockerfile for Cypress
# cypress/included:cypress-15.12.0-node-24.14.0-chrome-146.0.7680.80-1-ff-148.0.2-edge-146.0.3856.62-1
# https://hub.docker.com/layers/cypress/included/cypress-15.12.0-node-24.14.0-chrome-146.0.7680.80-1-ff-148.0.2-edge-146.0.3856.62-1/images/sha256-22677aed206249ca225b9d5300513964370ef6c74a998672a7c06f2770e37b38
FROM cypress/included@sha256:22677aed206249ca225b9d5300513964370ef6c74a998672a7c06f2770e37b38

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
