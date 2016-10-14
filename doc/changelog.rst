.. _changelog:

Changelog
=========

Version 1.3.0
-------------

* New features:

  * add a scheduler to allow booking (bookable) items from the database, on Team page (#238). Head to the admin panel to create a bookable type of item. You can then book it from the Team page.
  * add possibility to show experiments from others from the team. Go to the User Control Panel to set the option.
  * add possibility to send a mass email to all registered users from Sysconfig panel (#271)
  * Chemdoodle: when clicking the Save button on an experiment, the .mol file is automatically uploaded (#174)
  * Sysadmin can now edit users from the Sysadmin panel (#297)

* User interface (contributions by @manonstripes):

  * tooltips appear on icons to display their action
  * better colors for buttons depending on their purpose
  * language select is now displaying language in a user friendly way
  * homogeneization of some pages
  * prettier user interface
  * better user experience

* Bug fixes:

  * fix display of experiments by date (fix #296)
  * fix long lines overflowing on wells
  * fix locked item not editable onclick (thx Arti)
  * fix todolist keyboard shortcut input on user control panel

* Enhancements:

  * password reset link is now only valid for one hour (#297)
  * allow \\ in title and body (#300)

* Internationalization:

  * Catalan is 71% translated
  * Chinese is 68% translated
  * French is 100% translated
  * German is 98% translated
  * Italian is 83% translated
  * Polish is 25% translated
  * Portuguese is 64% translated
  * Portuguese (Brazilian) is 79% translated
  * Russian is 23% translated
  * Slovenian is 91% translated
  * Spanish is 100% translated

  Check the contributing page to help translate.

* Documentation:

  * the documentation has improved a lot
  * Docker install is now default with elabctl
  * add SafeCreative in the timestamping manual (thx @gebauer)

* Dev corner:

  * a whole lot more unit tests
  * code coverage has been enabled
  * acceptance tests are working properly. The config file is swapped for the test DB.
  * files in app/ were deleted and code was moved to classes
  * the inc/ folder is no more! files are in app/
  * updated bower components
  * updated composer components

* Security:

  * activate security switches in php config in docker image
  * add Content-Security-Policy header to docker image
  * add Strict-Transport-Security header to docker image

Version 1.2.6
-------------

* remove the counting of uploaded files (sysconfig page) because it may crash the php process for large number of files

Version 1.2.5
-------------

* fix bug leading to first user in a new team not having correct permissions (was not admin)

Version 1.2.4
-------------

* fix a missing `<div>` element from the sysconfig page preventing correct navigation through tabs

Version 1.2.3
-------------

* fix for MySQL 5.7.5+ (see #273)
* documentation improvements

Version 1.2.2
-------------

* fix a typo preventing users from resetting their password
* prevent duplicate tags from showing (#270)
* improve the install experience of installing in the cloud (use dialog)
* improve the documentation and code syntax

Version 1.2.1
-------------

* update the crypto lib to 2.0

WARNING DOCKER USERS !!!!! IMPORTANT READ BELOW:

Once you pull the new version and visit a page, the config file will be updated with a new secret key. You need to copy it from inside the container to your docker-compose.yml file!

1. Use `docker ps` to check the ID of the container (or use its name)

2. Replace $ID from the below command with your container ID (or name). This command will extract the new key and place it at the end of your config file.

.. code-block:: bash

    docker exec -it $ID grep SECRET /elabftw/config.php| awk -F \' '{print $4}' >> docker-compose.yml

3. Edit `docker-compose.yml` to replace the old SECRET_KEY value by the new one at the end of the file.

Like shown on this image:

.. image:: img/1.2.1.png
    :align: center
    :alt: update config

For normal users (no docker):

If you have a message asking you to make your config file readable, use this: `chmod 777 config.php`. Execute this command from inside the `elabftw` folder.
Refresh the page to retry. You can put back restrictive permissions after the update is done.

This update is a major update from the php-encryption project. So we need to change how the key is. This key is used to encrypt the SMTP and timestamping passwords.

* update a lot of composer components
* update JS components
* fix bug leading to new users being always validated
* add in-depth documentation for docker install

Version 1.2.0-p3
----------------

* fix bug leading to first user on fresh install not being sysadmin + admin

Version 1.2.0-p2
----------------

* fix install
* fix team groups
* remove wrong column in banned_users table
* remove username mention on statistics page

Version 1.2.0-p1
----------------

* fix imported csv without a title
* fix error in php 5.6 preventing sysconfig.php to show up

Version 1.2.0
-------------

* Big changes

  * The username is no more! Login with your email. That happened because:
     * Usernames were not used
     * People tend to forget the username they picked, but always remember their email
     * It simplifies the code by removing clutter

  * Timestamping with openssl has a bug! So we use Java.
     * See `this issue <https://github.com/elabftw/elabftw/issues/242>`_
     * TL;DR It is due to a bug in the OpenSSL library and a change on how the default TSA replies
     * If you install Java you can continue to timestamp
     * If you use Docker, updating the container is enough

* New features

  * Add possibility to promote a user to SysAdmin
  * Add possibility to delete an empty team
  * Add a way to test email configuration directly from config page
  * Add possibility to clear the logs
  * Show usage statistics on sysconfig page
  * Show informations about the server on sysconfig page
  * Allow searching for elabid
  * Add buttons to show more or show all items

* Enhancements

  * Improved layout for displaying users, status and items types
  * Improved translation for french, add terms
  * Better notification system
  * Improved "Create new" menus
  * Users using a docker container can now use Let's Encrypt certificates easily
  * Install on a drop is now using a Docker image, and automatic Let's Encrypt certificates

* Documentation

  * Better doc for install on Drop

* Developer corner

  * A lot of things changed under the hood, with the creation of app/models, views and controllers
  * Code moved around to try to have something that looks like an MVC seen from very far away
  * Optimize page load by doing less useless SQL requests
  * Add asynchronous calls everywhere
  * Updated composer components
  * Removed some duplicated code
  * Removed useless code
  * Better CSS code
  * Replace die and exit by Exceptions

Version 1.1.8-p2
----------------

* Bug fixes

  * fix deletion of thumbnails for non jpg images
  * fix name of timestamp pdf
  * fix image display in pdf (fix #234)

Version 1.1.8-p1
----------------

* Bug fixes

  * Fix footer of profile page incorrect

* Documentation

  * Better doc for everything

* Enhancements

  * Remove 'LIMIT 100' on some SQL requests
  * Use download.php to display images. Fix #232

* Developer corner

  * Remove update.php script

Version 1.1.8
-------------

* Bug fixes

  * fix bug where elabid wasn't properly imported from zip archive
  * fix bug in docker where secret_key was absent from config file

* Documentation

  * clarified the Docker installation

* Enhancements

  * improved the docker distribution

Version 1.1.7
-------------

* Bug fixes

  * fix bug where list text size was incorrect (fixed upstream by tinymce devs; #158)
  * fix bug where color of items/status was wrong after editing it
  * fix bug in Docker implementation missing SECRET_KEY value in config file
  * fix bug in SQL syntax of the show action for tags

* Enhancements

  * You can now link experiments directly in text with the `#` autocomplete (fix #191)
  * Search page: when searching for experiments of the whole team, you'll get a list of tags from the whole team
  * Tags autocomplete: now showing completion from the team's tags
  * Molecular structure files (PDB/MOL2/SDF/mmCIF) are previewed using 3Dmol.js (fix #213) Thanks @Athemis.
  * Default hashing algorithm for files changed from md5 to sha256 (thanks @Athemis)
  * Add a pretty loader for autocomplete

* Developer corner

  * use grunt to minify all the JS and CSS files in one
  * updated composer and bower components
  * created the Upload class

Version 1.1.6
-------------

* Bug fixes

    * fix bug on capitalized images extensions (fix #195)
    * fix bug where quotes could break the mention plugin
    * fix bad login url sent to validated users (thx Joke)

* Enhancements

    * Better view on low resolution display (fix #204)
    * Disallow empty title in quicksave
    * add autocomplete to DB items (fix #190)
    * Change new version available banner color
    * Add absract display on mouse hover (fix #196)
    * Add download .asn1 button on timestamped experiments
    * Add autocomplete=off on admin page form
    * Add possibility to have floating images (fix #186)

* Documentation
    * Better manual

* Developer corner
    * use colorpicker instead of colorwheel, remove raphael.js dependance

Version 1.1.5-p2
----------------

* Hotfix : fix bug in permissions on DB items export (zip/pdf) (#183)

Version 1.1.5-p1
----------------

* Hotfix : fix bug in smtp password encryption (#182)

Version 1.1.5
-------------

* Bug fixes

    * fix bug on pdf generation: md5 sum of files not showing
    * fix 'Error getting latest version from server'
    * fix cookies not working properly
    * fix bug related to deletion of files upon user deletion

* New features

    * add user groups (check it out in the admin panel: visibility of experiments can now be set on a group of team members
    * add Remember me button on login page
    * add autocompletion to experiments (write # to get item list) (fix #65)

* Enhancements

    * new registered users will get the server lang as lang
    * tag list on search page is now filtered by selected user
    * improve zip import now also imports attached files to an item (fix #21)
    * add .elabftw.json file in zip archives (to allow easy reimport)
    * remove MANIFEST file from zip archives
    * remove .export.txt file from zip archives

* Documentation

    * move doc to reStructeredText (in doc/_build/html)
    * documentation is hosted at https://elabftw.rtfd.org
    * remove clutter on README.md (and add BADGES!!)

* Developer corner
    * add unit and acceptance tests
    * update composer components
    * use `Defuse/php-encryption <https://github.com/defuse/php-encryption/>`_ for encryption library
    * add API documentation (in doc/api)
    * class Db is a singleton
    * numerous code improvements (see git log)


Version 1.1.4-p3
----------------

* fix bug on install page

Version 1.1.4-p2
----------------

* fix INSTALLED_VERSION constant so it displays correctly if an update is available in sysconfig

Version 1.1.4-p1
----------------
* fix bug in zip/csv generation

Version 1.1.4
---------------

* fix bug in search page showing tags of other teams
* fix bug in search page returning items from other teams
* add ordering options to items types, status and templates (try sorting them!)
* add possibility to export experiments templates to a file (.elabftw.tpl)
* add possibility to import a template from a .elabftw.tpl file
* add possibility to import .elabftw.zip archives in the database
* switch to pki.dfn.de as default timestamper (it is free)
* revamp the timestamping class
* timestamping is properly validated
* add pagebreak tag in editor
* max file upload size is now based on system configuration (thx @jcapellman)
* move creation/duplication functions to Create() class
* timestamped pdf is now in the exported zip along with the .asn1 token
* removed check for update button in footer
* check for latest version on sysconfig page
* various little improvements and bug fixes
* update tinymce to 4.1.10
* update jquery to 2.1.4
* update SwiftMailer to 5.4.1

Version 1.1.3
-------------

* add new way to send emails (thanks to @Athemis)
* add new visibility setting (organization)
* add user guide in doc/ folder
* fix bug on experiment duplication
* display version in sysconfig page
* update pt-BR translation (thanks Kikuti)
* code cleaning

Version 1.1.2-p1
----------------

* fix css layout
* fix german translation (thanks Athemis)
* update JS components (bower update)
* update PHP components (composer update)
* use PSR-4 for autoloading classes

Version 1.1.2
-------------

* add :rfc:`3161` compatible trusted timestamping (#100)
* add filtering options (#15)
* add encryption for passwords of SMTP and Timestamp stored in the SQL database (#129)
* add a check for curl extension at install (#141)
* add hidden field to prevent bot registration (#84)
* fix team_id not added on db tag add
* fix no experiments/db item showing if there is no tags
* update mpdf library
* update swiftmailer library

Version 1.1.1
-------------

* add a CONTRIBUTING file to help contributors
* add tag in search (#63)
* fix a bug where images where not added to timestamp pdf (#131)
* fix a bug in SQL install file (only impacts new installs)

Version 1.1.0
-------------

* multiple file upload now possible
* add ChemDoodle on Team page
* add a bash script in install folder to help beginners
* fix a bug where the top right search bar was not searching at the good place if the lang was not english
* add a log view for the sysadmin
* various little improvements in code
* fix a CSS bug with Chemdoodle
* fix a bug where a file was not properly deleted from system

Version 1.0.0
-------------

* no changes from beta

Version 1.0.0-beta
------------------

* changelog is now in markdown
* move some files in doc/ folder
* improve download.php code
* add deps to composer.json

Version 1.0.0-alpha
-------------------

* different folder structure

Version 0.12.6
--------------

* better docker/haproxy integration
* show counter of unvalidated users to admin

Version 0.12.5
--------------

* add possibility to update via the web

Version 0.12.4
--------------

* add languages : Catalan, Spanish, German and Italian
* easier install on docker
* fix a bug where wrong admin was informed of new user

Version 0.12.0
--------------

* new todolist
* 1 step less for install
* internationalization (only English, Brazilian, Chinese and French at the moment)
* use of gettext for i18n
* the font is now loaded locally
* use bootstrap for css disposition
* fix some issues reported by users
* a lot of other things
* like really a lot of little stuff

Version 0.11.0
--------------

* So many things…

Version 0.10.2
--------------

* Add a possibility for timestamping a pdf export of an experiment
* Removed old update.php content
* Add md5sum to uploaded files
* Display md5sum of attached files in the pdf

Version 0.10.1
--------------

* Fix a bug in authentification
* Error logs make their apparition in the database
* l33t theme is no more
* Removed the github ssl cert (was not used anyway)
* Move files around (js dependencies in js/)
* Better bower integration


Version 0.10.0
--------------

* Support of several teams on the same install
* Fixed a bug in the search page
* Added groups for better permissions control
* Add MANIFEST file in zip archive
* Add lock info in pdf
* Minor bugs fixing and improvements
* A lot of other things

Version 0.9.5
-------------

* Use of bower to keep track of dependencies
* HTML5 video and audio can now be added
* Add a user preference to ask before leaving an edit page
* Add CSV file to ZIP exports
* Add a revision system (to be able to see old versions of an experiment)
* Add body to CSV export

Version 0.9.4.2
---------------

* Add import CSV page
* Add general template for experiments
* Add linked items and comments on PDF
* Easier install on Mac and Windows
* Add linked items list to pdf and list of attached files
* Add links button in editor
* Add image button in editor
* Add URL in CSV export
* Show the lock on database item
* Removed the html from zip export
* Fix div blocks not passing the filter and losing formatting (thx David !)
* Fix a bug with lock/unlock of items
* Fix a bug in zip generation

Version 0.9.4.1
---------------

* Status are now fully editable
* Bugfixes and cosmetic improvements

Version 0.9.4
-------------

* Security improvements against CSRF
* Config is now stored in the database and editable on admin page
* Add detection of login attempts, and configurable ban time and number of tries
* You can only unlock a lock experiment if you are the locker.
* Only a user with locking rights can lock an experiment of someone else.
* You can now forbid users to delete an experiment with a setting in the conf file
* You can add comments on experiments
* Date is now YYYYMMDD
* Email setup is no more mandatory on install
* Updated some js libraries
* Add a 'Saved' notification upon saving with the Save button of TinyMCE
* Clearer code

Version 0.9.3
-------------

* Add item type to folder of zip export
* Add useragent on github API request (checkforupdates)
* Add items locks
* Bugfixes and improvements

Version 0.9.2
-------------

* mpdf replaced html2pdf for pdf creation
* the check for updates button is fixed
* the minimum password size is now 8 characters
* HTTPS is now the only way to use eLabFTW
* install is now easier
* various bugfixes and improvements

Version 0.9.1
-------------

* Possibility to limit the visibility of an experiment to yourself only

Version 0.9
-----------

* Newer versions of JQuery and JQuery UI
* config.ini is now config.php
* Cosmetic changes
* Ctrl-Shift-D will add the date in the editor
* Possibility to search experiments owned by a unique user
* Conformation to coding standard PSR-2

Version 0.8.2
-------------

* Added check for updates button
* TinyMCE 4
* Editor'save button saves date, title and body

Version 0.8.1
-------------

* Admin can reset password
* You can search in everyone's experiments if you want

Version 0.8
-----------

* You can upload big files now
* Better register form
* Fix in html zip export
* Better name of zip files when there is only one experiment
* Bug fixes and improvements

Version 0.7.3.2
---------------

* Apparition of the view arrow to fix the tab opening behavior
* Clicking a tag will now make a search in the tags only
* No more root user, admin user is made on install
* Force https
* Fix bugs
* Upgrade the mail library (swift)
* Documentation for backup

Version 0.7
-----------

* Multiple bugfixes
* Real search page
* Possiblity to export in zip or spreadsheet
* Thumbnails are clickable
* Better pdf generation
* Better html generation
* Install is now easier

Version 0.6
-----------

* Swith repo from gitorious to github (because it has wiki, bug tracker, and bigger community)
* Items in DB can now be everything, and you can edit them
* Improvement on reset password strategy
* eLabID is a unique ID bound to each experiment (useful for tracking raw data)
* Star ratings are shown on DB show mode
* You can lock for edition an experiment
* Autosave every second on edit
* Improvements in .zip creation
* Multiple bugfixes
* Show linked experiments to a database item

Version 0.5.8 and 0.5.9
-----------------------

* I don't really care about version numbers, I do it for fun.

Version 0.5.7
-------------

* Database
* Publish button
* TinyMCE for editing the body (text formatting)
* Better info boxes
* Better presentation of UCP
* Better search

Version 0.5.6
-------------

* Various bugfixes

Version 0.5.5
-------------

* Calendar on date
* Autocomplete on tags
* Ajax for tags

Version 0.5.4
-------------

* Added modification «history» on protocols
* Added dates on labmeeting and journal clubs uploads

Version 0.5.3
-------------

* Added templates for experiments
* You can now upload past journal clubs, labmeetings
* Added robots.txt file

Version 0.5.2
-------------

* TODO list accessible via a keyboard shortcut ('t' by default)
* Better profile
* Better TEAM page

Version 0.5.1
-------------
* No more Scriptaculous/Prototype, only jQuery
* TODO list added
* Images are now in themes folders
* Various FTW titles
* Git repo @ gitorious

Version 0.5
-----------

* UCP
* Themes
* Keyboard Shortcuts
* View modes
* Admin Panel
* Profile
* Send zip by email
* Better Tagcloud
* Can attach protocol to experiment
* User need validation after registration
* Unique config.ini file

Version 0.4
-----------

* Tagcloud
* Recover password
* Make zip archive
* Editable file comments

Version 0.3
-----------

* Tags on a separate table
* Make pdf
* Statistics
* Comment on attached files
* Quick tagsearch

Version 0.2
-----------

* Search page
* Password storage using salted SHA-512
* Attaching files

Version 0.1
-----------

* Register / Login
* Show / view / edit / duplicate :: experiments / protocols
