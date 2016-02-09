.. _install-docker:

Install in a Docker container
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

At the first startup, a private key will be generated. You need to get it from the running container to store it in your docker-compose.yml file.

Grab the CONTAINER ID of the elabftw container

.. code-block:: bash

    $ docker ps

Grab the secret key

.. code-block:: bash

    $ docker exec -t 733d32c1de22 grep KEY /elabftw/config.php
    # replace the ID with yours, tab completion should work

Now put it in your docker-compose.yml for the next time you want to relaunch the container.

.. code-block:: yaml

    environment:
        - DB_NAME=elabftw
        - DB_USER=elabftw
        - DB_PASSWORD=secr3t
        - SECRET_KEY=ddc467e42f72535636e87029656ab662

That's all folks!
