# syntax=docker/dockerfile:1.3
# Dockerfile for Cypress
# https://hub.docker.com/layers/cypress/included/cypress-15.20.0-node-24.19.0-chrome-151.0.7922.75-1-ff-153.0.3-edge-151.0.4129.59-1/images/sha256-9862f4bd2e6c85d7a8f3445a0d40d3445b62c4cf70ae18dfb743b49dd23dfb19
# we use the index digest
FROM cypress/included@sha256:5baa6e2f1831e7b9823246d22b5914fe77187a9bab030aa2f44fceabde17c9d1

WORKDIR /home/node

COPY containers/cypress/package.json containers/cypress/package-lock.json .

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
