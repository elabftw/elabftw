Usage
=====

Installation
------------

The easiest way to get started is to use Composer_ using the following steps:

1. Create a file name ``composer.json`` with the following contents:

   .. code-block:: json

      {
          "require": {
              "cilex/cilex": "1.0.*@dev"
          }
      }

2. Download Composer_ and install the project

   .. code-block:: bash

      $ curl -s http://getcomposer.org/installer | php
      $ php composer.phar install

Upgrading
---------

Cilex can be upgraded using Composer_ by calling the update command:

   .. code-block:: bash

      $ php composer.phar update

Bootstrap
---------

The simplest example of a Cilex application is by including the autoloader, instantiating the Cilex application class
and calling the ``run`` method. The following example demonstrates this principle:

.. code-block:: php

   <?php
   // application.php

   require_once __DIR__ . '/vendor/autoload.php';

   $app = new \Cilex\Application('NameOfMyApplication');

   // list of commands

   $app->run();

Calling your new application is as simple as the following line:

.. code-block:: bash

   $ php application.php

Even without actual commands you are greeted with an informative description what to do next:

.. code-block:: bash

    NameOfMyApplication version

    Usage:
      [options] command [arguments]

    Options:
      --help           -h Display this help message.
      --quiet          -q Do not output any message.
      --verbose        -v Increase verbosity of messages.
      --version        -V Display this application version.
      --ansi              Force ANSI output.
      --no-ansi           Disable ANSI output.
      --no-interaction -n Do not ask any interactive question.

    Available commands:
      help   Displays help for a command
      list   Lists commands

.. _Composer: http://getcomposer.org
