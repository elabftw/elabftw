# Changelog for eLabFTW

# Version 1.1.4
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
* timestamped pdf is now in the exported zip
* removed check for update button in footer
* check for latest version on sysconfig page
* various little improvements and bug fixes

# Verson 1.1.3
* add new way to send emails (thanks to @Athemis)
* add new visibility setting (organization)
* add user guide in doc/ folder
* fix bug on experiment duplication
* display version in sysconfig page
* update pt-BR translation (thanks Kikuti)
* code cleaning

# Version 1.1.2-p1
* fix css layout
* fix german translation (thanks Athemis)
* update JS components (bower update)
* update PHP components (composer update)
* use PSR-4 for autoloading classes

# Version 1.1.2
* add RFC 3161 compatible trusted timestamping (#100)
* add filtering options (#15)
* add encryption for passwords of SMTP and Timestamp stored in the SQL database (#129)
* add a check for curl extension at install (#141)
* add hidden field to prevent bot registration (#84)
* fix team_id not added on db tag add
* fix no experiments/db item showing if there is no tags
* update mpdf library
* update swiftmailer library

# Version 1.1.1
* add a CONTRIBUTING file to help contributors
* add tag in search (#63)
* fix a bug where images where not added to timestamp pdf (#131)
* fix a bug in SQL install file (only impacts new installs)

# Version 1.1.0
* multiple file upload now possible
* add ChemDoodle on Team page
* add a bash script in install folder to help beginners
* fix a bug where the top right search bar was not searching at the good place if the lang was not english
* add a log view for the sysadmin
* various little improvements in code
* fix a CSS bug with Chemdoodle
* fix a bug where a file was not properly deleted from system

# Version 1.0.0
* no changes from beta

# Version 1.0.0-beta
* changelog is now in markdown
* move some files in doc/ folder
* improve download.php code
* add deps to composer.json

# Version 1.0.0-alpha
* different folder structure

# Version 0.12.6
* better docker/haproxy integration
* show counter of unvalidated users to admin

# Version 0.12.5
* add possibility to update via the web

# Version 0.12.4
* add languages : Catalan, Spanish, German and Italian
* easier install on docker
* fix a bug where wrong admin was informed of new user

# Version 0.12.0
* new todolist
* 1 step less for install
* internationalization (only English, Brazilian, Chinese and French at the moment)
* use of gettext for i18n
* the font is now loaded locally
* use bootstrap for css disposition
* fix some issues reported by users
* a lot of other things
* like really a lot of little stuff

# Version 0.11.0
* So many things…

# Version 0.10.2
* Add a possibility for timestamping a pdf export of an experiment
* Removed old update.php content
* Add md5sum to uploaded files
* Display md5sum of attached files in the pdf

# Version 0.10.1
* Fix a bug in authentification
* Error logs make their apparition in the database
* l33t theme is no more
* Removed the github ssl cert (was not used anyway)
* Move files around (js dependencies in js/)
* Better bower integration


# Version 0.10.0
* Support of several teams on the same install
* Fixed a bug in the search page
* Added groups for better permissions control
* Add MANIFEST file in zip archive
* Add lock info in pdf
* Minor bugs fixing and improvements
* A lot of other things

# Version 0.9.5
* Use of bower to keep track of dependencies
* HTML5 video and audio can now be added
* Add a user preference to ask before leaving an edit page
* Add CSV file to ZIP exports
* Add a revision system (to be able to see old versions of an experiment)
* Add body to CSV export

# Version 0.9.4.2
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

# Version 0.9.4.1
* Status are now fully editable
* Bugfixes and cosmetic improvements

# Version 0.9.4
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

# Version 0.9.3
* Add item type to folder of zip export
* Add useragent on github API request (checkforupdates)
* Add items locks
* Bugfixes and improvements

# Version 0.9.2
* mpdf replaced html2pdf for pdf creation
* the check for updates button is fixed
* the minimum password size is now 8 characters
* HTTPS is now the only way to use eLabFTW
* install is now easier
* various bugfixes and improvements

# Version 0.9.1
* Possibility to limit the visibility of an experiment to yourself only

# Version 0.9
* Newer versions of JQuery and JQuery UI
* config.ini is now config.php
* Cosmetic changes
* Ctrl-Shift-D will add the date in the editor
* Possibility to search experiments owned by a unique user
* Conformation to coding standard PSR-2

# Version 0.8.2
* Added check for updates button
* TinyMCE 4
* Editor'save button saves date, title and body

# Version 0.8.1
* Admin can reset password
* You can search in everyone's experiments if you want

# Version 0.8
* You can upload big files now
* Better register form
* Fix in html zip export
* Better name of zip files when there is only one experiment
* Bug fixes and improvements

# Version 0.7.3.2
* Apparition of the view arrow to fix the tab opening behavior
* Clicking a tag will now make a search in the tags only
* No more root user, admin user is made on install
* Force https
* Fix bugs
* Upgrade the mail library (swift)
* Documentation for backup

# Version 0.7
* Multiple bugfixes
* Real search page
* Possiblity to export in zip or spreadsheet
* Thumbnails are clickable
* Better pdf generation
* Better html generation
* Install is now easier

# Version 0.6
* Swith repo from gitorious to github (because it has wiki, bug tracker, and
bigger community)
* Items in DB can now be everything, and you can edit them
* Improvement on reset password strategy
* eLabID is a unique ID bound to each experiment (useful for tracking raw
data)
* Star ratings are shown on DB show mode
* You can lock for edition an experiment
* Autosave every second on edit
* Improvements in .zip creation
* Multiple bugfixes
* Show linked experiments to a database item

# Version 0.5.8 and 0.5.9
* I don't really care about version numbers, I do it for fun.

# Version 0.5.7
* Database
* Publish button
* TinyMCE for editing the body (text formatting)
* Better info boxes
* Better presentation of UCP
* Better search

# Version 0.5.6
* Various bugfixes

# Version 0.5.5
* Calendar on date
* Autocomplete on tags
* Ajax for tags

# Version 0.5.4
* Added modification «history» on protocols
* Added dates on labmeeting and journal clubs uploads

# Version 0.5.3
* Added templates for experiments
* You can now upload past journal clubs, labmeetings
* Added robots.txt file

# Version 0.5.2
* TODO list accessible via a keyboard shortcut ('t' by default)
* Better profile
* Better TEAM page

# Version 0.5.1
* No more Scriptaculous/Prototype, only jQuery
* TODO list added
* Images are now in themes folders
* Various FTW titles
* Git repo @ gitorious

# Version 0.5 Released 2012.03.01
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

# Version 0.4
* Tagcloud
* Recover password
* Make zip archive
* Editable file comments

# Version 0.3
* Tags on a separate table
* Make pdf
* Statistics
* Comment on attached files
* Quick tagsearch

# Version 0.2
* Search page
* Password storage using salted SHA-512
* Attaching files

# Version 0.1
* Register / Login
* Show / view / edit / duplicate :: experiments / protocols
