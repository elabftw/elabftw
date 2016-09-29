.. _upgrade-to-docker:

Upgrade a normal install to a Docker install
============================================

.. image:: img/docker.png
    :align: center
    :alt: docker

Description
-----------

This guide is aimed at (GNU/Linux) users who have installed eLabFTW the old school way (git clone or zip archive) and want to benefit from Docker.
If you are not familiar with Docker, take the time to read the documentation on the Docker website. And read also the :ref:`in-depth documentation for eLabFTW with Docker <docker-doc>`.

Preparation
-----------

Install Docker
``````````````
We will obviously need to `install Docker <https://docs.docker.com/engine/installation/linux/>`_.

Once this is done try a:

.. code-block:: bash

    docker run hello-world

If everything works, you should see a little message explaining what Docker did to print this message.

Install elabftw normally
````````````````````````
Follow the steps described :ref:`here <install>`, except the last one. Do not start the containers.

Export current install
``````````````````````
Before going further, make sure you have backups of the existing installation. Read how to backup :ref:`here <backup>`.

You should have a `dump.sql` file of the elabftw database. And your `uploads` folder is copied somewhere safe.

Installation
------------

You now have a configuration file `/etc/elabftw.yml`. Edit it with your favorite editor.

Editing the config file
```````````````````````
* Open the `config.php` file located in your `elabftw` folder of the current install
* Copy the SECRET_KEY value from the `config.php` file to the `docker-compose.yml` file
* Port: change "443:443" to "8080:443" or "444:443" or "9000:443" because your current server is already using port 443
* If you are running MySQL 5.5 or 5.6, edit the 5.7 in the `image: mysql:5.7` line to the appropriate version. You can upgrade later.

Copy the uploaded files
```````````````````````
* Copy your `uploads` folder to `/var/elabftw/web`

About HTTPS (SSL/TLS)
`````````````````````
As you know, eLabFTW can only run with HTTPS. So if you were running it before, there are good chances that you already have a certificate. If it's a self-signed one, nothing needs to be done. The Docker image will generate a new certificate when the container is created. But your users will get a warning when they access the website, which is not ideal.

One solution to this is to request a certificate from `Let's Encrypt <https://letsencrypt.org>`_. It's free and you get a real proper certificate. See the documentation on Let's Encrypt website on how to request a certificate for your website. You will need to have a domain name (it doesn't work if you just have an IP address or if the server is not accessible from outside a company's network) pointing to the server.

Another solution is to use the certificate you already have.

* Change the value of ENABLE_LETSENCRYPT to true in `/etc/elabftw.yml`
* Uncomment the line `#- /etc/letsencrypt:/ssl` (remove the leading #)
* If your domain is `elabftw.example.com`, do this:

.. code-block:: bash

    # as root
    mkdir -p /etc/letsencrypt/live/elabftw.example.com/
    cp /path/to/your/current-cert.pem /etc/letsencrypt/live/elabftw.example.com/fullchain.pem
    cp /path/to/your/current-key.pem /etc/letsencrypt/live/elabftw.example.com/privkey.pem

Another way to do this is to `git clone` the `docker-elabftw` repo and edit the `src/run.sh` script to point to the correct directory, but this will not be covered in this guide.

Starting the containers
```````````````````````
.. code-block:: bash

    elabctl start

This will create an empty database in `/var/elabftw/mysql`. But of course, what we want is to have our old database in there! To do that we will copy our `dump.sql` file to the `mysql` container and import it in place of the freshly created database (which is empty!).

.. code-block:: bash

    docker cp dump.sql mysql:/
    docker exec -it mysql bash
    mysql -uroot -p
    # here you type the password you put in MYSQL_ROOT_PASSWORD in the docker-compose.yml file
    Mysql> drop database elabftw;
    Mysql> create database elabftw;
    Mysql> use elabftw;
    Mysql> source dump.sql;
    Mysql> exit;

You should now have your old database running. If you were upgrading from an old version, make sure to read the release note of each version. Specifically the 1.2.1 one where there is a manual step to copy the new SECRET_KEY to the docker-compose.yml.

Test everything is working by clicking everything. Report any problem in the present documentation so it can be improved.

As always, if you need help, open a github issue :)
