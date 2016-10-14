.. _contributing:

Contributing
============

.. image:: img/contributing.png
    :align: center
    :alt: contributing
    :target: http://mimiandeunice.com/

What can you do to help this project ?
--------------------------------------

* `Star it on GitHub <https://github.com/elabftw/elabftw>`_
* Talk about it to your colleagues, spread the word!
* Have a look at `the current issues <https://github.com/elabftw/elabftw/issues>`_
* Help with the translation
* Open GitHub issues to discuss bugs or suggest features

.. image:: img/i18n.png
    :align: right

Translating
-----------

Do you know several languages? Are you willing to help localize eLabFTW? You're in the right place.

How to translate ?

* `Join the project on poeditor.com <https://poeditor.com/join/project?hash=aeeef61cdad663825bfe49bb7cbccb30>`_
* Select elabftw
* Add a language (or select an existing one)
* Start translating
* Ignore things like `<strong>, <br>, %s, %d` and keep the ponctuation like it was!

Translations are updated before a release.

Contributing to the code
------------------------

Environment installation
````````````````````````
* Fork `the repository <https://github.com/elabftw/elabftw>`_ on GitHub
* From your fork page, clone it on your machine (let's say it's in `/home/user/elabftw`)
* :ref:`Install eLabFTW <install>` normally (with **elabctl**) but don't start it
* Git clone the docker-elabftw repo and build a dev image:

.. code-block:: bash

    git clone -b dev https://github.com/elabftw/docker-elabftw
    cd docker-elabftw
    docker build -t elabftw/dev .

* Edit the docker-compose configuration file `/etc/elabftw.yml`
* Change the `volumes:` line so it points to the `elabftw` folder, not `elabftw/uploads`. So the line should look like : /home/user/elabftw:/elabftw
* Change the line `image:` and replace it with `elabftw/dev`
* Optionaly change the port binding from 443 to something else (example: 9999:443)
* Start the containers:

.. code-block:: bash

   sudo elabctl start

* You now should have a running local eLabFTW, and changes made to the code will be immediatly visible

Making a pull request
`````````````````````
#. Before working on a feature, it's a good idea to open an issue first to discuss its implementation
#. Create a branch from **hypernext**
#. Work on a feature
#. Make a pull request on GitHub to include it in hypernext

Code organization
`````````````````
* Real accessible pages are in the root directory (experiments.php, database.php, login.php, etc…)
* The rest is in app/
* app/models will contain classes with CRUD (Create, Read, Update, Destroy)
* app/views will contain classes to generate and display HTML
* app/classes will contain services or utility classes
* a new class will be loaded automagically thanks to the use of PSR-4 with composer (namespace Elabftw\\Elabftw)
* app/controllers will contain pages that send actions to models (like destroy something), and generally output json for an ajax request, or redirect the user.

Ðependencies
````````````
* PHP dependencies are managed through `Composer <https://getcomposer.org/>`_
* JavaScript dependencies are managed through `Bower <https://bower.io/>`_

i18n
````
* for internationalization, we use gettext
* i18n related things are in the `locale` folder
* the script `locale/genpo.sh` is used to merge the french .po file from extracted strings
* if you add a string shown to the user, it needs to be gettexted _('like this')

Miscellaneous
`````````````
* if you make a change to the SQL stucture, you need to put add an update function in `app/classes/Update.php` and also modify `install/elabftw.sql` accordingly
* instead of adding your functions to `app/functions.inc.php`, create a proper class
* you can use the constant ELAB_ROOT (which ends with a /) to have a full path
* comment your code wisely
* your code must follow `the PSR standards <https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md>`_
* add a plugin to your editor to show trailing whitespaces in red
* add a plugin to your editor to show PSR-1 errors
* remove BOM
* if you make a change to the documentation, you can regenerate the HTML with `grunt doc`
* install grunt with :

.. code-block:: bash

    $ npm install grunt grunt-contrib-uglify grunt-contrib-watch grunt-contrib-cssmin grunt-shell
    $ npm install -g grunt-cli


API Documentation
`````````````````

You can find a PHP Docblock generated documentation on classes `here <../../../doc/api/namespaces/Elabftw.Elabftw.html>`_ (local link).

Have a look at the errors report to check that you commented all functions properly.

To generate it: `grunt api`

Automation
``````````

Since version 1.1.7, elabftw uses `grunt <http://gruntjs.com/>`_ to minify and concatenate files (JS and CSS). Have a look at Gruntfile.js to see what it does. Install grunt-cli and run it if you make changes to any of those files.
Grunt can also be used to build the documentation or run the tests.

.. code-block:: bash

    $ grunt # will minify and concatenate JS and CSS
    $ grunt doc # will build this documentation
    $ grunt api # will build the API documentation
    $ grunt test # will run the tests with codeception

.. note:: You need to have a running `Selenium server <http://docs.seleniumhq.org/download/>`_ to do the acceptance tests


Reminders
`````````

* for a new version, one needs to edit app/classes/Update.php, package.json and doc/conf.py

Make a gif
``````````

* make a capture with xvidcap, it outputs .xwd

* convert .xwd to gif:

.. code-block:: bash

    $ convert -define registry:temporary-path=/path/tmp -limit memory 2G \*.xwd out.gif
    # or another way to do it, this will force to write all to disk
    $ export MAGICK_TMPDIR=/path/to/disk/with/space
    $ convert -limit memory 0 -limit map 0 \*.xwd out.gif

* generate a palette with ffmpeg:

.. code-block:: bash

    $ ffmpeg -i out.gif -vf fps=10,scale=600:-1:flags=lanczos,palettegen palette.png

* make a lighter gif:

.. code-block:: bash

    $ ffmpeg -i out.gif -i palette.png -filter_complex "fps=10,scale=320:-1:flags=lanczos[x];[x][1:v]paletteuse" out-final.gif

* upload to original one to gfycat and the smaller one to imgur
