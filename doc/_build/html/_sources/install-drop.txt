.. _install-drop:

Install in a drop
=================

.. image:: img/digitalocean.png
    :align: center
    :alt: digitalocean

Some people make you pay to have a 'cloud service'. What I'm providing here is a very simple way to install eLabFTW on your own server (drop).

This way you get to keep total control over your data. It will cost you less than 5$ a month. No setup fee, no annual licence, welcome to open source software ;)

Very minimal technical knowledge is required to follow the instructions (you know what is a domain name? you know how to open a terminal? you're good to go).

Your eLabFTW installation will run in a `Docker <https://www.docker.com>`_ container. Privacy over the wire (HTTPS) will be provided by `Let's Encrypt <https://letsencrypt.org>`_.

Everything will be configured properly and automagically.

Create your drop
----------------

.. warning:: This script will work for a fresh drop. If you already have a server running, you should consider a :ref:`normal install <install-gnulinux>` instead.



* Create an account on `DigitalOcean <https://cloud.digitalocean.com/registrations/new>`_

* Create a droplet with Docker (from the One-click Apps tab), select a size and a region.

* It might be a good idea to enable backups

* Add your SSH key (`documentation <https://www.digitalocean.com/community/tutorials/how-to-use-ssh-keys-with-digitalocean-droplets>`_)

* Create the drop (it takes a minute)

* Copy the IP address

* Go to the control panel of your domain name provider. Point your domain to the IP address of your drop. It might take a bit of time for the DNS to propagate (a few hours).

.. warning:: You need to have a domain name pointing to the drop. Otherwise the Let's Encrypt script WILL NOT work.

Install everything
------------------

* Open a terminal and connect to your new server.

.. code-block:: bash

    ssh root@12.34.56.78

* Go inside a tmux session:

.. code-block:: bash

    tmux

* Enter the following magical command:

.. code-block:: bash

    wget -qO- https://get.elabftw.net > drop-elabftw.sh && bash drop-elabftw.sh

.. danger:: Don't get into the habit of executing unknown scripts as root without reading them first!

.. hint:: To follow the install progress, open a new pane with Ctrl-b, release and press %. Then enter ``tail -f elabftw.log``

* Read what is displayed at the end.

ENJOY! :D
