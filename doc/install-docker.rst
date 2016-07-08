.. _install-docker:

Install in a Docker container
=============================

.. image:: img/docker.png
    :align: center
    :alt: docker

Description
-----------

Using Docker allows you to run elabftw without touching the configuration of your server or computer. By using this docker image you don't have to worry about missing php extensions or misconfigurations of the server because all was done for you beforehand.

Install
-------

Step 1 : install `docker <https://docs.docker.com/engine/installation/>`_ and `docker-compose <https://docs.docker.com/compose/install/>`_

Step 2 : enter the following three commands:

.. code-block:: bash

    # get the config file
    wget https://raw.githubusercontent.com/elabftw/docker-elabftw/master/src/docker-compose.yml-EXAMPLE -O docker-compose.yml
    # edit it and add the secret key, change default passwords, change mount points for volumes, change ports mapping
    $EDITOR docker-compose.yml
    # fire up everything
    docker-compose up -d

Now visit https://YOUR.IP.ADDRESS/install. You will be redirected to the register page to create the sysadmin account.

That's all folks! The documentation below is a bonus, read it if you want to understand more how it works or if you didn't manage to configure the app.

.. _docker-doc:

In-depth documentation
----------------------

How does it work?
`````````````````
Running eLabFTW with Docker means everything needed will be provided. You'll have the correct version of every library and the webserver will be properly setup with a secure configuration. Because eLabFTW will run in a container. In fact you'll have two containers running. One will be from the official MySQL image, running a MySQL server. The other will be the eLabFTW image, with a webserver + PHP and the eLabFTW files. In order to facilitate the whole process, we will use `docker-compose <https://docs.docker.com/compose/install/>`_. This tool will allow us to do the configuration in a YAML file, easy to modify and copy around, and also permit easy start/stop of the containers.

About the docker image
``````````````````````
- The elabftw docker image is using `Alpine Linux <https://alpinelinux.org/>`_ as a base OS, so we get a lightweight and secure base.
- `PHP 7 <https://secure.php.net/>`_ is used so we get an up to date and fast PHP.
- `Nginx <http://nginx.org>`_ is used so we get the best webserver out there running our app with `HTTP/2 <https://en.wikipedia.org/wiki/HTTP/2>`_ capabilities.

Using Docker, you'll also automatically benefit from some additional security features:

- header X-Frame-Option
- header X-XSS-Protection
- header X-Content-Type-Options
- header Strict-Transport-Security
- use Diffie-Hellman for key exchange with 2048 bits parameter
- use modern cipher suite and protocols for SSL. This will result in an A rating on `SSLLabs <https://www.ssllabs.com/ssltest/>`_.

Editing the docker-compose.yml file
```````````````````````````````````
If you've never done that before, it can look scary, hence this extended documentation ;)

The first thing you need to change is the value of DB_PASSWORD. It is advised to use a very long and complex password, as you won't have to remember it. Use can use `this page <https://www.grc.com/passwords.htm>`_ to get a password. Make sure to put the same in MYSQL_PASSWORD. Change also MYSQL_ROOT_PASSWORD.

Then get a secret key from the `provided url <https://demo.elabftw.net/install/generateSecretKey.php>`_ and paste it in SECRET_KEY
(this key is used to encrypt the smtp password).

In Docker, the containers are not persistant, this means that changes made will vanish when the container is removed.

But we need to have persistant data of course, so what we will do is tell Docker that some directories will in reality be on the host. We need the uploads folder, and the MySQL database. You can have those folders anywhere, just make sure the permissions are not too restrictive.

In the example configuration file, there is a /dok folder at the root with a subfolder for the uploaded files, and another one for the SQL database. So in order to use this, one would need to run this command (as root):

.. code-block:: bash

    mkdir -pvm 777 /dok/{uploads,mysql}


Using docker-compose
````````````````````

Commands below must be executed in the directory containing the docker-compose.yml file.


Start the containers:

.. code-block:: bash

    docker-compose up -d

Check what is currently running:

.. code-block:: bash

    docker-compose ps

Stop everything:

.. code-block:: bash

    docker-compose down

Update the images (update elabftw):

.. code-block:: bash

    docker-compose pull


Using the Let's Encrypt certificates
````````````````````````````````````

If your server has a domain name pointing to it, you can ask Let's Encrypt to give you SSL certificates. It is highly recommended to do that. Also, self-signed certificates will show a warning on the browser, which is an annoyance.

Change ENABLE_LETSENCRYPT to true, and uncomment the letsencrypt volume line. Because certificates are on the host, we need a volume to use them from the container.

.. note:: If you use the install on a drop, letsencrypt certificates will be configured automatically for you
