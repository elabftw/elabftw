.. _common-errors:

Common errors
=============


Add the secret key
------------------

Starting from version 1.1.2 there is a secret key present in the config file. If you have restrictive permissions on it, the webserver won't be able to add it when you run the update.php script. To fix this issue, the simplest way is to:

.. code-block:: bash


    chmod a+w config.php

Then run the /update.php script again. And bring back good permissions afterwards:

.. code-block:: bash

    chmod 400 config.php

You might need to use ``sudo`` to achieve these commands.

Failed creating *uploads/* directory
------------------------------------

If eLabFTW couldn't create an *uploads/* folder, that's because the httpd user (www-data on Debian/Ubuntu) didn't have the necessary rights. To fix it you need to:

1. Find what is the user/group of the web server. There is a good chance that it is www-data. But it might also be something else. Run the `install.sh` script in the `install` folder.

2. Now that you know the user/group of the webserver, you can do that (example is shown with www-data, but adapt to your need):

.. code-block:: bash

    cd /path/to/elabftw
    mkdir -p uploads/tmp
    chown -R www-data:www-data uploads
    chmod -R 755 .
    chmod 400 config.php

The last line is to keep your config file secure. It might fail because the file is not there yet. Finish the install and do it after then.

If you have problems updating (git pull is failing because of permissions), read more about GNU/Linux permissions and groups. For instance, you can add your user to the www-data group:

.. code-block:: bash

    usermod -a -G www-data `whoami`

Extension is not loaded
-----------------------

Install everything needed by elabftw:

.. code-block:: bash

    sudo apt-get install php-gettext php5-gd php5-curl libapache2-mod-php5

Now reload the Apache server:

.. code-block:: bash

    sudo service apache2 reload

I can't upload a file bigger than 2 Mb
--------------------------------------

Edit the file php.ini and change the value of upload_max_filesize to something bigger, example:

.. code-block:: bash

    upload_max_filesize = 128M

.. warning:: Don't forget to remove the `;` at the beginning of the line !

I can't export my (numerous) experiments in zip, I get an error 500
-------------------------------------------------------------------

Edit the file `/etc/php/php.ini` or any file called php.ini somewhere on your filesystem. Try `sudo updatedb;locate php.ini`. For XAMPP install, it is in the config folder of XAMPP.
Now that you have located the file and opened it in a text editor, search for `memory_limit` and increase it to what you wish. `Official documentation on memory_limit <http://php.net/manual/en/ini.core.php#ini.memory-limit>`_.

You can also increase the value of max_execution_time and max_input_time.
Then restart your webserver:

.. code-block:: bash

    sudo service apache2 restart

Languages don't work
--------------------

eLabFTW uses `gettext <https://en.wikipedia.org/wiki/Gettext>`_ to translate text. This means that you need to have the associated locales on the server.
To see what locale you have::

    locale -a

To add a locale, edit the file `/etc/locale.gen` and uncomment (remove the #) the locales you want. If you don't find this file you can try directly the command::

    locale-gen fr_FR.UTF-8

Replace with the locale you want, of course.
See :doc:`here <contributing>` to see a list of languages (and locales) supported by eLabFTW.
Then do::

    sudo locale-gen

And reload the webserver.
