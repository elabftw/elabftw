---
sidebar_position: 2
title: Installing a dev environment
---

# Development environment installation

The dev environment for eLab is an hybrid between Docker and a local install. The files will be served by the webserver in Docker but the source code (`elabftw` folder) will be on your computer. This allows you to run the app as it would run in production, but still see your changes in the code immediately because the source is on your computer. In this setup, we will put everything in the same folder for simplicity.

Ready?

## Docker install

* Make sure that you can run "docker" and "docker compose" commands. Install them:

  * [Docker](https://www.docker.com)
  * [Docker Compose](https://docs.docker.com/compose/)

Make sure your user is in the `docker` group so you can execute docker commands without sudo (see `documentation <https://docs.docker.com/install/linux/linux-postinstall/>`_).

:::note
Some issues may occur when using Docker Desktop to manage your containers. It is highly recommended to use the system's native Docker daemon instead.
:::

## Dev directory setup

* Next let's define a directory for dev (adapt to your needs):

~~~bash
# this folder can be anywhere you like
export dev='/home/<YOUR USERNAME>/elabdev'
mkdir -p $dev
cd $dev
~~~

## Forking the repo

* Go on [the repository on GitHub](https://github.com/elabftw/elabftw)
* Click the Star button (it helps with visibility of the project)
* Click the Fork button in the top right of the screen
* Uncheck the box "Copy only the master branch" (we will work on another branch)
* From your fork page, clone it with SSH on your machine:

~~~bash
git clone git@github.com:<YOUR USERNAME>/elabftw.git
~~~

## Install elabctl

`elabctl` is a tool to manage your installation. It is not strictly required but it's a "nice to have".

* Get `elabctl` and the configuration files:

~~~bash
# get elabctl
curl -sLo elabctl https://get.elabftw.net && chmod +x elabctl
# get elabctl configuration file
curl -so elabctl.conf https://raw.githubusercontent.com/elabftw/elabctl/master/elabctl.conf
~~~

* Edit `elabctl.conf`, change `BACKUP_DIR` to `$dev/backup` or any other directory (write full paths of course, not aliases)
* Change `CONF_FILE` to `$dev/docker-compose.yml`. Again, write the full path, not the alias!
* Change `DATA_DIR` to `$dev/elabftw`. Again, write the full path, not the alias!

## Install compose file

The `docker-compose.yml` file is the main configuration file for eLabFTW. It defines what containers to start, and how you want them configured.

Get the `docker-compose.yml` configuration file, it will automatically be filled with random passwords and a new `SECRET_KEY`:

~~~bash
curl -so docker-compose.yml "https://get.elabftw.net/?config"
~~~

* Edit the `docker-compose.yml` configuration file
* For the web container, use "image: elabftw/elabimg:edge" so you are using the latest container image for dev
* Set `DEV_MODE` to `true`

:::note
The `DEV_MODE` relaxes the content security policy slightly, and turns off the extra safety net of a somewhat restrictive `open_basedir <https://www.php.net/manual/en/ini.core.php#ini.open-basedir>`_ directive (values found in [docker-entrypoint.sh](https://github.com/elabftw/elabimg/blob/master/src/entrypoint/docker-entrypoint.sh)). Avoid having this enabled in production systems.
:::

* Change the `ports:` line so the container runs on port 3148 (you can choose whatever port you want, or leave it on 443). It should look like this:

~~~yaml
ports:
    - "3148:443"
~~~


* set `SITE_URL` to `https://localhost:3148` or whatever port you chose in the previous step.
* Change the `volumes:` lines to bind mount the container to the source code. Paths are formatted as `SOURCE:DESTINATION`, in which the source path is the path located on the local file directory and the destination path is the path located on the Docker container.
    * For the elabftw container: Adjust the source path to point to `$dev/elabftw`. Adjust the destination path to `/elabftw`.
    * For the mysql container: Adjust the source to point to `$dev/mysql`. Keep the destination path as `/var/lib/mysql`.

The lines should look like this:

~~~yaml
services:
    web:
        volumes:
            - ~/elabdev/elabftw:/elabftw
    mysql:
        volumes:
            - ~/elabdev/mysql:/var/lib/mysql
~~~


* Start the containers:

~~~bash
./elabctl start
~~~

## Install dependencies

:::note
PHP dependencies are managed through `Composer <https://getcomposer.org/>`_. It'll read the `composer.lock` file and install packages (see `composer.json`). Javascript dependencies are managed through `Yarn <https://yarnpkg.com/>`_. It'll read the `yarn.lock` file and install packages (see `package.json`). The `yarn install` command will populate the `node_modules` directory, and the `buildall` command will use `Webpack <https://webpack.js.org/>`_ to create bundles (see `builder.js` file).
:::

* Now install the JavaScript and PHP dependencies using `yarn` and `composer` shipped with the container:

~~~bash
cd $dev/elabftw
# javascript dependencies (node_modules/ directory)
docker exec -it elabftw yarn install
docker exec -it elabftw yarn buildall
# php dependencies (vendor/ directory)
docker exec -it elabftw composer install
~~~

:::note
It can be a good idea to define an alias such as "alias elabc=docker exec -it elabftw". So you can use "elabc" to run commands in the container directly.
:::

It is important to run `yarn` before `composer` because `yarn` will generate a PHP class that needs to be picked up by composer.

## Install the database

* Initialize the database structure with:

~~~bash
docker exec -it elabftw bin/init db:install
~~~


## Finishing up

* Now head to https://localhost:3148
* You now should have a running local eLabFTW, and changes made to the code will be immediately visible

It is possible to populate your dev database with fake generated data. See the `db:populate` command of `bin/init`.
