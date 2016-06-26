.. _install-windows:

Install on Windows (in Docker)
==============================

.. image:: img/windows.png
    :align: center
    :alt: windows

Installing eLabFTW on Windows is not your typical Setup.exe > Next > Next > Finish install. Because it is a server software, we will run it on a server. And this server will be inside a container, run by `Docker <https://www.docker.com>`_, which is itself run by GNU/Linux in a virtual machine. It might look complicated at first, but be not afraid, everything is explained.

Follow the steps below to install eLabFTW on your system:

#. Read the documentation and install `Docker Toolbox for Windows <https://docs.docker.com/toolbox/toolbox_install_windows/>`_
#. `Download this configuration file template <https://raw.githubusercontent.com/elabftw/docker-elabftw/master/src/docker-compose.yml-EXAMPLE>`_
#. Save it as docker-compose.yml
#. Edit it with `Notepad++ <https://notepad-plus-plus.org/>`_ or any editor you like but not plain old Notepad.
#. Check out the :ref:`in-depth documentation <install-docker>` if you feel lost.
#. Save it as docker-compose.yml (make sure there is no .txt extension)
#. Open Docker Quickstart Terminal
#. Enter these commands:

.. code-block:: bash

    cd Desktop #(or wherever you saved docker-compose.yml)
    docker-compose.exe up -d

**Final step** wait that the previous command finishes and click here: https://192.168.99.100/install

.. note:: Remember to keep your installation :doc:`backuped <backup>` and :doc:`updated <how-to-update>` ;)
