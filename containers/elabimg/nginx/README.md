# Nginx configuration files

## Description

Nginx configuration templates are installed in `/etc/nginx`; runtime-generated configuration is written to `/run/nginx`.

Some configuration files contain placeholders (`%EXAMPLE%`) that are replaced by a correct value at runtime (script [docker-entrypoint.sh](../entrypoint/docker-entrypoint.sh)).

## Files

- `nginx.conf` is the main entrypoint, it loads files in `conf.d` folder
- `conf.d` folder will also contain a symbolic link to either `http.conf` or `https.conf` depending on what we want to run
- `common.conf` contains common configuration options for elabftw server (http or https)

## Configuration

`Nginx` is configured and compiled with only the bare minimum, see the build step in `Dockerfile`. It also sets a custom 404 error page.
