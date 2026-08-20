---
sidebar_position: 2
title: Installing a dev environment
---

# Development environment installation

The dev environment for eLab is a hybrid between Docker and a local install. The files will be served by the webserver in Docker but the source code (`elabftw` folder) will be on your computer. This allows you to run the app as it would run in production, but still see your changes in the code immediately because the source is on your computer.

## Pre-requisites

- `mkcert`: we will use mkcert to generate local certificates that the browser will trust (we run the local instance in https)

⇒ **[Install mkcert](https://github.com/filosottile/mkcert#linux)**.

- `docker` and its `compose` plugin.

⇒ **[Install Docker](https://www.docker.com)**

⇒ **[Install Docker Compose](https://docs.docker.com/compose/)**

Make sure your user is in the `docker` group so you can execute docker commands without sudo (see [documentation](https://docs.docker.com/engine/install/linux-postinstall/)).

:::note
Some issues may occur when using Docker Desktop to manage your containers. It is highly recommended to use the system's native Docker daemon instead.
:::

## Forking the repo

* Go on [the repository on GitHub](https://github.com/elabftw/elabftw)
* Click the Star button (it helps with visibility of the project)
* Click the Fork button in the top right of the screen
* From your fork page, clone it with SSH on your machine:

~~~bash
git clone git@github.com:<YOUR USERNAME>/elabftw.git
cd elabftw
~~~

## Init

From the project's root:

~~~bash
./containers/elabdev/init.sh
~~~

This will create bind-mounted directories and generate the certificate and key for TLS. It will install JS and PHP dependencies and populate the database with fake data.

You only need to run the `init.sh` script once.

Later, use `./containers/elabdev/start.sh` if you just need to start them.

Use `./containers/elabdev/stop.sh` to stop them. There is also `clean.sh` in there, to start fresh.

:::note
It can be a good idea to define an alias such as "alias elabc=docker exec -it elabftw". So you can use "elabc" to run commands in the container directly.
:::

It is important to run `yarn` before `composer` because `yarn` will generate a PHP class that needs to be picked up by composer.

## Finishing up

* Now head to https://elab.localhost:3148
* You now should have a running local eLabFTW, and changes made to the code will be immediately visible
