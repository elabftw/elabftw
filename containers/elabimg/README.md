# elabimg

## Overview

This is the main Docker image for [eLabFTW](https://www.elabftw.net). It runs four services:

* nginx: the webserver
* php-fpm: to handle requests to PHP files
* chronos: executes tasks on a schedule (similar to a cronjob)
* invoker: executes tasks asynchronously (useful for exports for instance)

These services are managed by s6-overlay and are all heavily customized for running eLabFTW.

## Tags

Tags, in the context of Docker images, are what comes after the image name (`elabftw/elabimg`), separated by a colon. If you leave it empty, it defaults to `latest`, which will point to the latest stable release. But it is recommended to explicitly define the version you wish to run, e.g.: `elabftw/elabimg:5.3.4`.

When defining which image version to use, you can use different tags:

* `x.y.z`: the actual explicit version: recommended
* `stable`: the latest stable version
* `latest`: the latest stable version
* `edge`: the latest tagged version, can be a stable version or an alpha or beta, whatever is present on `master` branch of eLabFTW

## Building this image

From the repository root folder:

~~~bash
docker build -t elabftw/elabimg:X.Y.Z --build-arg ELABFTW_VERSION=X.Y.Z -f containers/elabimg/Dockerfile .
~~~

For dev, add `--build-arg BUILD_ALL=0` to skip the installation of dependencies and building of assets, because the folder will be bind-mounted to your host anyway. `ELABFTW_VERSION` can be omitted in dev.

## Usage

An example configuration file for docker-compose can be fetched like this:

~~~bash
curl -so docker-compose.yml "https://get.elabftw.net/?config"
~~~

After downloading the configuration file, open it in your preferred text editor to modify settings as necessary.

For usage of eLabFTW, see [documentation](https://doc.elabftw.net).

## Reloading services

If for some reason you wish to reload a service without restarting the container (for instance when trying configuration changes), you can use ``reload``:

~~~bash
# reload php and nginx
reload
# reload only php
reload php
# reload only nginx
reload nginx
~~~

## Deleting GitHub build cache

If there is a CVE fixed upstream, delete build cache with:

~~~bash
gh cache delete --all
~~~

Requires GitHub CLI: https://cli.github.com/
