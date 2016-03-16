.. _install-docker:

Install in a Docker container
=============================

.. image:: img/docker.png
    :align: center
    :alt: docker

Description
-----------

Using Docker allows you to run elabftw without touching the configuration of your server or computer. By using this docker image you don't have to worry about missing php extensions or misconfigurations of the server because all was done for you beforehand.

- This docker image is using `Alpine Linux <https://alpinelinux.org/>`_ as a base OS, so we get a lightweight and secure base.
- PHP 7 is used so we get an up to date and fast PHP.
- Nginx is used so we get the best webserver out there running our app.
- An official MySQL docker image is used for the database.

Install
-------

Step 1 : install `docker <https://docs.docker.com/engine/installation/>`_ and `docker-compose <https://docs.docker.com/compose/install/>`_

Step 2 : enter the following three commands:

.. code-block:: bash

    # get the config file
    wget https://raw.githubusercontent.com/elabftw/docker-elabftw/master/src/docker-compose.yml-EXAMPLE -O docker-compose.yml
    # edit it and add the secret key, change default passwords, change mount points for volumes, change ports mapping
    $EDITOR docker-compose.yml
    # fire up everything
    docker-compose up -d

Now visit https://YOUR.IP.ADDRESS/install. You will be redirected to the register page to create the sysadmin account.

That's all folks!
