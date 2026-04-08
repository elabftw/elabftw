# eLabFTW Chemistry plugin

## Description

This image contains two services:

1. Ketcher molecule editor service (indigo-service)
2. Fingerprinting service with OpenBabel

## Build

~~~
docker build -t elabftw/chem-plugin .
~~~

## Usage

Deploy it on the same network as eLabFTW and configure eLabFTW to use this service.

### With HTTPS

Use `TLS_KEYFILE` and `TLS_CERTFILE` env vars pointing to the key/cert files.

## Acknowledgements

The fingerprinting part of this project was initialized by Tanguy Le Carrour from [Easter-Eggs](https://www.easter-eggs.com/). Thank you Tanguy for the MVP and discussions!

## Indigo service

This image builds on code from https://github.com/epam/Indigo, the configuration files in `conf` have been modified to run as user, and a supervisord configuration file has been added. The files in `service` are untouched (we just removed the `tests` folder). We completed the `Dockerfile` from Indigo with additions necessary for eLabFTW chem plugin.
