---
sidebar_position: 7
title: Upgrade
---

# How to Upgrade


:::warning
Be sure to read the [release notes](https://github.com/elabftw/elabftw/releases/latest), they might contain important information. And have a [backup](/docs/install/backups).
:::

:::note
If you are running out of disk space, you can do "docker system prune -a" to free up some space taken by old images.
:::

## STEP 0: Before the update

Make sure to figure out what version you are running and **read the release notes** for the new version.

The current running version can be seen in the bottom right of every page.

:::warning
Be sure to read the [release notes](https://github.com/elabftw/elabftw/releases/latest), they might contain important information. And have a [backup](/docs/install/backups).
:::

## STEP 1: Specify which version you want

Start by editing your configuration file (`/etc/elabftw.yml` by default) and change the version of the image (line `image: elabftw/elabimg:X.Y.Z`)

The latest version can be found on [this page](https://github.com/elabftw/elabftw/releases/latest).

:::note
You can also use the tag `latest` or `stable` which will always point to the latest stable release.
:::


## STEP 2: Launch a new container

### With elabctl

~~~
elabctl update
~~~

### Without elabctl

In the directory where you have the `docker-compose.yml` file:

~~~bash
docker compose pull
docker compose down
docker compose up -d
~~~

## STEP 3: Run the database migration

~~~
# change the name of the container if it is different in your configuration
docker exec -it elabftw bin/console db:update
# Note: for version 3.3 to 3.4 use this instead
docker exec -it elabftw bin/console db:updateTo34
# Note: for version 2.x to 3.x use this instead
docker exec -it elabftw bin/console db:updateto3
~~~

Congratulations, you are now running the latest version! Make sure to keep your installation regularly updated!

If you encounter an issue during the database migration, open a GitHub issue!

Note that you can use `db:revert XYZ` to revert the changes made by schema `XYZ`, or use `--force` to ignore errors (only do that if you know what you are doing!).

## Updating from incredibly old versions

If you are running eLabFTW version `<3.4.0` (from March 2020), you'll want to run `db:updateto34` after upgrading to 3.4.0 (again, always read the release notes from the version you're targeting).

If you are running eLabFTW version `<3.0.0` (from April 2019), you'll want to run `db:updateto3` after upgrading to 3.0.0.

If you are running eLabFTW version `<2.0.7` (from December 2018), you'll want to update more often so you're not stuck with an incredibly outdated software application.
