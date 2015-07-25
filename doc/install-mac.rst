.. _install-mac:

Install on Mac OS X
===================


This guide assumes you want to install eLabFTW locally for your personnal use (not shared with other team members). As eLabFTW is designed with a client <=> server architecture in mind, we'll need to install a server on your computer. Don't worry though, it's easy :)

Install XAMPP
-------------

Download `XAMPP for OS X <http://www.apachefriends.org/download.html>`_. Take the greatest version number (second link).

Now that it's downloaded, double click it and open the installer. You can untick the XAMPP Developer Files and Learn more about Bitnami checkboxes.

Once it's installed, you let it start XAMPP. On the application manager (/Applications/XAMPP/manager-osx.app):

* Go to the tab '''Manage Servers'''
* Select MySQL Database
* Click Start

Test that everything is working by going to https://localhost. You should see a warning that the certificate is not signed and cannot be trusted, which is normal.

`Download the latest release <https://github.com/elabftw/elabftw/releases/latest>`_ and extract its content to `/Applications/XAMPP/htdocs/elabftw`.

Now we need to fix the permissions. Open the terminal and type::

    cd /Applications/XAMPP/htdocs/elabftw
    mkdir -p uploads/tmp
    chmod -R 777 .

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


.. blah










