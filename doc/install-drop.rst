.. _install-drop:

Install in a drop
=================

.. image:: img/digitalocean.png
    :align: center
    :alt: digitalocean

A drop is a server in the cloud, and is pretty cheap. You can install elabftw on it with one command: everything is automagic!

Your elabftw installation will run in a docker container.

.. warning:: This script will work for a fresh drop. If you already have a server running, you should consider a :ref:`normal install <install-gnulinux>` instead.


.. warning:: You need to have a domain name pointing to the drop. Otherwise the letsencrypt script will not work.

* Point your domain to the IP address of your drop. It might take a bit of time for the DNS to propagate.

* Create an account on `DigitalOcean <https://cloud.digitalocean.com/registrations/new>`_

* Create a droplet with Docker (from the One-click Apps tab), select a size and a region.

* It might be a good idea to enable backups

* Add your SSH key (`documentation <https://www.digitalocean.com/community/tutorials/how-to-use-ssh-keys-with-digitalocean-droplets>`_)

* Once created, open a terminal and SSH to your new server. The IP address can be found on the digitalocean website:

.. code-block:: bash

    ssh root@12.34.56.78

* Go inside a tmux session:

.. code-block:: bash

    tmux

* Enter the following magical command:

.. code-block:: bash

    wget -qO- https://get.elabftw.net > install-elabftw.sh && sh install-elabftw.sh

.. danger:: Don't get into the habit of executing unknown scripts as root without reading them first!

.. hint:: To follow the install progress, open a new pane with Ctrl-b, release and press %. Then enter ``tail -f elabftw.log``

* Read what is displayed at the end.

ENJOY! :D
