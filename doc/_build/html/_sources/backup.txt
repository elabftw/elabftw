.. _backup:

How to backup
=============

This page shows you how to backup an existing elabftw installation. It is important that you take the time to make sure that your backups are working properly.

.. image:: img/didyoubackup.jpg

There is basically three things to backup :

* the MySQL database
* your `config.php` file (unless you're using Docker and you want the `docker-compose.yml` file)
* the uploaded files (in `uploads/` folder)

Using elabctl
-------------

If you installed eLabFTW with elabctl, making a backup becomes very easy. Issue this command as root:

.. code-block:: bash

    elabctl backup

Using a script
--------------

You'll want to have a little script that do the backup automatically.
Here is one way to do it. Adapt it to your needs: `see script <https://gist.github.com/NicolasCARPi/5d9e2599857a148a54b0>`_.

If you don't remember your SQL user/password, look in the `config.php` file!

Make sure to synchronize your files to another computer. Because backuping to the same machine is only half useful.

Making it automatic using cron
------------------------------

A good backup is automatic.

If you're under a GNU/Linux system, try::

    export EDITOR=nano ; crontab -e

This will open a file:

.. image:: img/crontab.png

Add this line at the bottom::

    00 04 * * * bash /path/to/backup.sh
    or
    00 04 * * * elabctl backup

This will run the script everyday at 4am.

How to backup a Docker installation
-----------------------------------

With elabctl
````````````
If you installed eLabFTW with `elabctl <https://github.com/elabftw/elabctl>`_, use:

.. code-block:: bash

    elabctl backup

Without elabctl
```````````````
* Copy the docker-compose.yml somewhere safe.
* Copy the `uploads` folder somewhere safe (make a zip or tar archive).
* Backup the MySQL database:

.. code-block:: bash

    docker exec -it mysql bash -c 'mysqldump -u$MYSQL_USER -p$MYSQL_PASSWORD -r dump.sql $MYSQL_DATABASE'
    docker cp mysql:dump.sql elabftw-$(date --iso-8601).sql
    gzip --best elabftw-$(date --iso-8601).sql


* A script doing all of the above is available `here <https://gist.github.com/NicolasCARPi/711bdd8b9dca2aaa69457d71583c0fae>`_.
* Make sure to run it periodically.


How to restore a backup
-----------------------

Get a fresh elabftw folder, make the required directories, copy the config file:

.. code-block:: bash

    git clone --depth 1 https://github.com/elabftw/elabftw
    mkdir -p elabftw/uploads/tmp
    chmod -R 777 elabftw/uploads
    cp -r /path/to/backup/uploads elabftw/
    cp /path/to/backup/config.php elabftw/

Now import your SQL database back

You can use phpmyadmin, create a new database and import your .sql backup, or use the command line:

.. code-block:: bash

    gunzip /path/to/backup/elabftw.sql.gz
    mysql -uroot -p elabftw < /path/to/backup/elabftw.sql


Stay safe ;)
