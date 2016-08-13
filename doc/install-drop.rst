.. _install-drop:

Install in the cloud
====================

Some people make you pay to have a 'cloud service'. What I'm providing here is a very simple way to install eLabFTW on your own server (drop), cutting the middleman.

This way you get to keep total control over your data. It will cost you less than 5$ a month. No setup fee, no annual licence, no overpriced features, welcome to open source software ;)

One other advantage is that you'll get your own server. With it, you can run whatever you want, not just eLabFTW! A `Wiki <https://www.mediawiki.org/wiki/MediaWiki>`_, a bug tracker, `GitLab <https://about.gitlab.com/>`_, etc…

Your eLabFTW installation will run in a `Docker <https://www.docker.com>`_ container. Learn more about eLabFTW in Docker :ref:`here <docker-doc>`.

Everything will be configured properly and automagically.

If you don't have a (sub)domain already, you can get one from `OVH <https://www.ovh.com>`_, `Gandi <https://www.gandi.net>`_, `1&1 <https://www.1and1.com>`_ or any other domain name registrar. You can get one for half a dollar per year.


Create your drop
----------------

.. warning:: This script will work for a fresh drop. If you already have a server running, you should consider a :ref:`normal install <install-gnulinux>` instead.

* Create an account on `DigitalOcean <https://m.do.co/c/c2ce8f861e0e>`_

.. image:: img/digitalocean.png
    :align: center
    :alt: digitalocean

* Create a droplet with Docker (from the One-click Apps tab), select a size and a region.

.. image:: img/image-selection.png
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

* Install `elabctl`:

.. code-block:: bash

    wget -qO- https://get.elabftw.net > /usr/bin/elabctl && chmod +x /usr/bin/elabctl

* Install and start elabftw in Docker:

.. code-block:: bash

    elabctl install

* Use `elabctl` without arguments to see what you can do with it.

* Don't forget to read :ref:`the post install page <postinstall>`, setup :ref:`backup <backup>`, and subscribe to `the newsletter <http://elabftw.us12.list-manage1.com/subscribe?u=61950c0fcc7a849dbb4ef1b89&id=04086ba197>`_!

ENJOY! :D
