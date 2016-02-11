.. _install-docker:

Install in a Docker container
=============================

.. warning:: This is experimental!

.. image:: img/docker.png

Using Docker allows you to run elabftw without touching the configuration of your server.
It ships with nginx + php-fpm with elabftw installed and configured properly with all the php extensions needed.
An official MySQL docker image is used for the database.

Step 1 : install `docker <https://docs.docker.com/engine/installation/>`_ and `docker-compose <https://docs.docker.com/compose/install/>`_

Step 2 : enter the following three commands:

.. code-block:: bash

    # get the config file
    wget https://raw.githubusercontent.com/elabftw/docker-elabftw/master/docker-compose.yml-EXAMPLE -O docker-compose.yml
    # edit it and add the secret key, change default passwords, change mount points for volumes, change ports mapping
    $EDITOR docker-compose.yml
    # fire up everything
    docker-compose up

Now visit https://YOUR.IP.ADDRESS/install. You will be redirected to the register page to create the sysadmin account.

That's all folks!
