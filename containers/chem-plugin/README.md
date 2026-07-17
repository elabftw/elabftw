# eLabFTW Chemistry plugin

## Description

This image contains one service:

1. Fingerprinting service with OpenBabel

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
