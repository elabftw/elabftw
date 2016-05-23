.. _install-drop:

Install in the cloud
====================

Some people make you pay to have a 'cloud service'. What I'm providing here is a very simple way to install eLabFTW on your own server (drop), cutting the middleman.

This way you get to keep total control over your data. It will cost you less than 5$ a month. No setup fee, no annual licence, no overpriced features, welcome to open source software ;)

Your eLabFTW installation will run in a `Docker <https://www.docker.com>`_ container. Privacy over the wire (HTTPS) will be provided by `Let's Encrypt <https://letsencrypt.org>`_. The webserver will be `nginx <http://nginx.org>`_. Operating systems will be `Ubuntu <http://www.ubuntu.com>`_ (`GNU <https://www.gnu.org>`_/`Linux <https://kernel.org>`_) and `Alpine Linux <https://alpinelinux.org/>`_.

Everything will be configured properly and automagically.

If you don't have a (sub)domain already, you can get one from `OVH <https://www.ovh.com>`_, `Gandi <https://www.gandi.net>`_, `1&1 <https://www.1and1.com>`_ or any other domain name registrar. It's about 5$ a year.

Create your drop
----------------

.. warning:: This script will work for a fresh drop. If you already have a server running, you should consider a :ref:`normal install <install-gnulinux>` instead.

* Create an account on `DigitalOcean <https://cloud.digitalocean.com/registrations/new>`_

.. image:: img/digitalocean.png
    :align: center
    :alt: digitalocean

* Create a droplet with Docker (from the One-click Apps tab), select a size and a region.

.. image:: img/do-create.png
    :align: center
    :alt: digitalocean

* Optional: enable backups (might be a good idea)

* Optional: add your SSH key (`documentation <https://www.digitalocean.com/community/tutorials/how-to-use-ssh-keys-with-digitalocean-droplets>`_)

* Create the drop (it takes a minute)

* Copy the IP address

* Optional: go to the control panel of your domain name provider. Point your domain (or subdomain) to the IP address of your drop. It might take a bit of time for the DNS to propagate (a few hours).

.. note:: Without a domain pointing to the drop, you will have a self signed certificate (so users will have a warning), whereas if you have a domain name, you will get a proper SSL certificate from Let's Encrypt.


Install everything
------------------

* Open a terminal and connect to your new server:

.. code-block:: bash

    ssh root@12.34.56.78

* Use a terminal multiplexer:

.. code-block:: bash

    tmux

* Enter the following magical command:

.. code-block:: bash

    wget -qO- https://get.elabftw.net > drop-elabftw.sh && bash drop-elabftw.sh

.. danger:: Don't get into the habit of executing unknown scripts as root without reading them first!

.. hint:: To follow the install progress, open a new pane with Ctrl-b, release and press %. Then enter ``tail -f elabftw.log``

* Read what is displayed at the end.

ENJOY! :D
