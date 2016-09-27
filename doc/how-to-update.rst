.. _how-to-update:

How to update
=============

.. warning:: Be sure to read the release notes, they might contain important information. And have a :ref:`backup <backup>`.

If you installed it with elabctl
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: bash

    elabctl update

If you installed it with git
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

To update, cd in the `elabftw` folder and do:

.. code-block:: bash

    git pull

If you are using Docker
^^^^^^^^^^^^^^^^^^^^^^^

In the directory where you have the `docker-compose.yml` file:

.. code-block:: bash

    docker-compose pull
    docker-compose down
    docker-compose up -d

If you installed it from a .zip or .tar.gz archive
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

1. Get the `latest archive <https://github.com/elabftw/elabftw/releases/latest>`_
2. Unpack it on your server, overwriting all the files.
