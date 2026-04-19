# syntax=docker/dockerfile:1.3
# Dockerfile for Cypress
# https://hub.docker.com/layers/cypress/included/cypress-14.5.4-node-22.18.0-chrome-139.0.7258.127-1-ff-141.0.3-edge-139.0.3405.86-1/images/sha256-620a38f0522a1bc90a941b697239a35567d6f2c305d6b119a8f82e81b906ad79
# we use the index digest
FROM cypress/included@sha256:848fb0d361178e695aa3ebd0f9632f2966232907c0fc02fbd6432e07d4d08d8b

WORKDIR /home/node

COPY tsconfig.json .
COPY cypress.config.ts .
COPY tests/cypress/ ./tests/cypress/
COPY tests/_data/ ./tests/_data/
COPY containers/cypress/package.json .
COPY containers/cypress/package-lock.json .

# create cypress output folders
# use bash so we can expand the command (RUN will use sh)
RUN bash -c 'mkdir -p tests/cypress/{videos,screenshots}'
RUN chown -R node:node tests/cypress

USER node

# add html validation tools to cypress
RUN npm ci --ignore-scripts

# overwrite default 'cypress run' entry point, will call it manually later
# https://github.com/cypress-io/cypress-docker-images/tree/master/included#keep-the-container
ENTRYPOINT ["tail", "-f", "/dev/null"]
