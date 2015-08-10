.. _install-windows:

Install on Windows (in Docker)
==============================

.. warning:: This is experimental!

As Windows is a huge pile of shit, we cannot install eLabFTW directly on it. We need a layer of sanity, called a Virtual Machine running GNU/Linux with Docker.

Being familiar with the Docker concepts is required. This guide is not complete. You might face some unexpected errors. You need 64 bits Windows. You have been warned.

Also, installing it in a Ubuntu VM will be easier. Docker is very nice when it works, but there are literally hundreds of way for it to fail.


#. Download and run **docker-install.exe** from `this page <https://github.com/boot2docker/windows-installer/releases>`_
#. Use the defaults in the installer (install everything)
#. Run the boot2docker application
#. Execute the command: `boot2docker ssh`
#. Install docker-compose for windows (`see here <https://stackoverflow.com/questions/29289785/how-to-install-docker-compose-on-windows>`_)
#. Make sure to have the right permissions so the docker container can read/write where it wants to

.. code-block:: bash

    git clone https://github.com/elabftw/docker-elabftw
    cd docker-elabftw
    cp docker-compose.yml-EXAMPLE docker-compose.yml
    $EDITOR docker-compose.yml
    docker-compose up

* Go to https://localhost:9000/install

You might need to change the networking setting of boot2docker VM from NAT to bridged or use port forwarding.
