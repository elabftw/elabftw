.. _install-drop:

Install in a drop
=================

.. image:: img/digitalocean.jpg


A drop is a cheap server, and you install elabftw on it with one command: everything is automagic!

The following actions will be performed :

- install of nginx (web server)
- install of  mariadb (sql server)
- install of elabftw
- get everything up and running

.. warning:: This script will work for a fresh drop. If you already have a server running, you should consider a :ref:`normal install <install-gnulinux>` instead.


* Create an account on `DigitalOcean <https://cloud.digitalocean.com/registrations/new>`_

* Create a droplet with Ubuntu 14.04 x64 (works also with 14.10, but not with 12.04.5)

* Open a terminal and SSH to your droplet. The IP address can be found in the digitalocean website:

.. code-block:: bash

    ssh root@12.34.56.78

.. note:: The root password is in your mailbox. It will not echo when you type it, it's normal, don't panic.

* Go inside a tmux session:

.. code-block:: bash

    tmux

* Enter the following command:

.. code-block:: bash

    wget -qO- https://get.elabftw.net|sh

.. danger:: Don't get into the habit of executing unknown scripts as root without reading them first!

.. hint:: To follow the install progress, open a new pane with Ctrl-b, release and press %. Then enter ``tail -f elabftw.log``

* Read what is displayed at the end.

ENJOY! :D
