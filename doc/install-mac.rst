.. _install-mac:

Install on Mac OS X
===================

.. image:: img/apple.png
    :align: center
    :alt: apple

.. warning:: eLabFTW should be installed on a server, not a personal computer. Installing it on your personal computer is totally fine, but in the end, what you want is to install it on a server so everyone in your team (or institute/company) can benefit from it. See :ref:`install in the cloud<install-drop>` if you don't have a server.

The steps below describe an installation of a web server (XAMPP) directly on your Mac. When Docker for Mac will work, we'll use that.

Install XAMPP
-------------

Download `XAMPP for OS X <https://www.apachefriends.org/download.html>`_. Take the greatest version number. Versions below 5.6 will **NOT** work.

Now that it's downloaded, double click it and open the installer. You can untick the XAMPP Developer Files and Learn more about Bitnami checkboxes.

Once it's installed, you let it start XAMPP. On the application manager (/Applications/XAMPP/manager-osx.app):

* Go to the tab '''Manage Servers'''
* Select MySQL Database
* Click Start
* Select Apache Webserver
* Click Start

Test that everything is working by going to https://localhost. You should see a warning that the certificate is not signed and cannot be trusted, which is normal. If it doesn't work, try telling your browser to avoid proxy for local addresses.

`Download the latest release <https://github.com/elabftw/elabftw/releases/latest>`_ and extract its content to `/Applications/XAMPP/htdocs/elabftw`.

Now we need to fix the permissions. Open the terminal and type:

.. code-block:: bash

    cd /Applications/XAMPP/htdocs/elabftw
    mkdir -p uploads/tmp
    sudo chmod -R 777 .

Setup a database
----------------

With XAMPP comes phpmyadmin. We will use this interface to do this part easily.

* Go to https://localhost/phpmyadmin
* Click on the Databases tab
* Create a database named `elabftw`

Final step
----------

Browse to : https://localhost/elabftw/install and follow onscreen instructions.

.. hint:: There is no password for the mysql user root. So put root as mysql username and no password.

.. note:: Remember to keep your installation :doc:`backuped <backup>` and :doc:`updated <how-to-update>` ;)
