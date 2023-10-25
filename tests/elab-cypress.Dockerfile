# syntax=docker/dockerfile:1.3
# Dockerfile for Cypress
FROM cypress/included:13.3.3

WORKDIR /home/node

USER node
# create cypress output folders
# this will prevent an error message if tests succeed and no screenshots are taken
# use bash so we can expand the command (RUN will use sh)
RUN bash -c 'mkdir -p tests/cypress/{videos,screenshots}'

# add html validation tools to cypress
RUN npm install typescript html-validate@^8 cypress-html-validate@^6

# copy everything because we can't bind mount
COPY cypress.config.ts .
COPY tests/cypress/ ./tests/cypress/
COPY tests/_data/ ./tests/_data/

# overwrite default 'cypress run' entry point, will call it manually later
# https://github.com/cypress-io/cypress-docker-images/tree/master/included#keep-the-container
ENTRYPOINT ["tail", "-f", "/dev/null"]
