.. _install:

Install on a GNU/Linux server
=============================

.. image:: img/gnulinux.png
    :align: center
    :alt: gnulinux

.. image:: img/docker.png
    :align: right
    :alt: docker

Prerequisites
-------------

eLabFTW uses `Docker containers <https://www.docker.com/what-docker>`_. So you need to install:

* `Docker <https://docs.docker.com/engine/installation/linux/>`_, the container engine
* and that's it!

.. note:: If you don't want to use Docker or cannot install it, have a look at :ref:`installing eLabFTW old school<install-oldschool>`.

Install eLabFTW
---------------

* Open a terminal and connect to your server:

.. code-block:: bash

    ssh you@your-server

* (optional) Use a terminal multiplexer:

.. code-block:: bash

    tmux

* Become root:

.. code-block:: bash

    sudo su

* Install `elabctl`:

.. code-block:: bash

    wget -qO- https://get.elabftw.net > /usr/bin/elabctl && chmod +x /usr/bin/elabctl

* Install eLabFTW in Docker:

.. code-block:: bash

    elabctl install

* (optional) Edit the configuration:

    You might want to edit the configuration here to suit your server setup. For instance, you might want to edit `/etc/elabftw.yml` to change the port binding (default is 443 but it might be already used). The best here is to have a load balancer (HAProxy) or reverse proxy (nginx).

* Start eLabFTW:

.. code-block:: bash

    elabctl start

* Use `elabctl` without arguments to see what you can do with it. Or `man elabctl`.

* Don't forget to read :ref:`the post install page <postinstall>`, setup :ref:`backup <backup>`, and subscribe to `the newsletter <http://elabftw.us12.list-manage1.com/subscribe?u=61950c0fcc7a849dbb4ef1b89&id=04086ba197>`_!

* The config file is `/etc/elabftw.yml` if you wish to adjust configuration to your setup.

ENJOY! :D
