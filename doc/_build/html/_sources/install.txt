.. _install:

Install on a GNU/Linux server
=============================

.. image:: img/gnulinux.png
    :align: center
    :alt: gnulinux

Tested distributions: Debian, Ubuntu, Fedora, CentOS, Arch Linux, OpenSUSE.

.. image:: img/docker.png
    :align: right
    :alt: docker

Prerequisites
-------------

eLabFTW uses `Docker containers <https://www.docker.com/what-docker>`_. So you need to:

* install `Docker <https://docs.docker.com/engine/installation/linux/>`_, the container engine
* and that's it!

.. note:: If you don't want to use Docker or cannot install it, have a look at :ref:`installing eLabFTW old school<install-oldschool>`.

Install eLabFTW
---------------

* Become root:

.. code-block:: bash

    sudo su

.. _normal-install:

* Install `elabctl`:

.. code-block:: bash

    wget -qO- https://get.elabftw.net > /usr/bin/elabctl && chmod +x /usr/bin/elabctl

* Configure eLabFTW:

.. code-block:: bash

    elabctl install

* (optional) Edit the configuration:

    You might want to edit the configuration here to suit your server setup. For instance, you might want to edit `/etc/elabftw.yml` to change the port binding (default is 443 but it might be already used by a traditional webserver). This is also where you can define where the data will be stored (default is /var/elabftw).

* Start eLabFTW:

.. code-block:: bash

    elabctl start

This step can take up to 3 minutes to complete because it'll generate strong Diffie-Hellman parameters. You can follow the output with:

.. code-block:: bash

    docker logs -f elabftw

Once started, you will see something like: nginx entered RUNNING state. You can now finalize the install with the last step.

* Register a Sysadmin account:

    Open \https://your-elabftw-site.org/**install** in your browser.

Post install
------------

Don't forget to read :ref:`the post install page <postinstall>`, setup :ref:`backup <backup>`, and subscribe to `the newsletter <http://elabftw.us12.list-manage1.com/subscribe?u=61950c0fcc7a849dbb4ef1b89&id=04086ba197>`_!

ENJOY! :D
