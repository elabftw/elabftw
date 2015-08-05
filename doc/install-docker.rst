.. _install-docker:

Install in a docker container
=============================

.. warning:: This is experimental!

.. image:: img/docker.png

This is for people who are familiar with docker.

.. code-block:: bash

    git clone https://github.com/elabftw/docker-elabftw
    cd docker-elabftw
    cp docker-compose.yml-EXAMPLE docker-compose.yml
    $EDITOR docker-compose.yml
    docker-compose up

If you need an image without MySQL, check out `this repository <https://github.com/NicolasCARPi/elabftw-docker-nosql>`_.
