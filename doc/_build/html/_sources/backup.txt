.. _backup:

How to backup
=============

This page shows you how to backup an existing elabftw installation.

There is basically two things to backup : the SQL database, and the `elabftw` folder (with your config file and your uploaded files).

Backup SQL database
-------------------

You'll want to have a little script that do the backup automatically.
Here is the script I'm using, adapt it to your needs::

    #!/bin/sh
    # backup.sh - Backup eLabFTW installation
    # ------------- 
    # Get clean date (make sure your version of date supports this option)
    y=`date --rfc-3339=date |awk -F '-' '{print $1}'|cut -c3-4`
    m=`date --rfc-3339=date |awk -F '-' '{print $2}'`
    d=`date --rfc-3339=date |awk -F '-' '{print $3}'`
    date=$y$m$d

    # elab sql backup
    ###################
    # make a dump of the database in elabftw.sql file
    mysqldump -u elabftw -p'PUT_YOUR_SQL_PASSWORD_HERE' elabftw > elabftw.sql

    # copy this file somewhere else using ssh (scp)
    scp -q elabftw.sql user@192.168.0.3:.backups/sql/elabftw-$date.sql

    # move the file to a local backup dir
    mv elabftw.sql /home/pi/.backups/sql/elabftw-$date.sql

    # www files backup
    ###################
    # make a tarball of www
    tar czf www.tar.gz -C /var/ www

    # copy the tarball to somewhere else using ssh (scp)
    scp -q www.tar.gz user@192.168.0.3:.backups/www/www-$date.tar.gz && rm www.tar.gz


If you don't remember your SQL password, look in the file `elabftw/config.php`!


Making it automatic using cron
------------------------------

A good backup is automatic.

If you're under a GNU/Linux system, try::

    export EDITOR=nano ; crontab -e

This will open a file.

.. image:: img/crontab.png

Add this line at the bottom::

    00 04 * * * sh /path/to/script.sh

This will run the script everyday at 4 am.

How to restore a backup
-----------------------

Get a fresh elabftw folder, make the required directories, copy the config file::

    git clone --depth 1 https://github.com/elabftw/elabftw
    mkdir -p elabftw/uploads/{tmp}
    chmod -R 777 elabftw/uploads
    cp -r /path/to/backup/uploads elabftw/
    cp /path/to/backup/config.php elabftw/

Now import your SQL database back

You can use phpmyadmin, create a new database and import your .sql backup, or use the command line::

    mysql -uroot -p elabftw < /path/to/backup/elabftw.sql


Stay safe ;)
