# docker-entrypoint.sh

## Description

This script is run only once when the container starts (oneshot s6 service).

It adjusts the configuration of nginx, php and elabftw based on environment variables provided.
