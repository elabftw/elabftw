# About
[eLaFTW (electronic lab for the world)](http://www.elabftw.net)
is a free and open-source experiments and database manager.
You can store your experiments (like an lab notebook, but better).
You can have a database of whatever objects (antibodies, siRNA, horses, plasmids, etc…).
You can search efficiently, export your results in a .zip archive or in a spreadsheet.
It was made by researchers, for researchers, with usability in mind.

Just try it, you'll love it :)

# Installation
Thank you for choosing eLabFTW as a lab manager =)
This file contains the instructions to install elabftw on a webserver,
but these instructions can also apply if you are installing it locally (with MAMP/WAMP).
Please report bugs on [github](https://github.com/NicolasCARPi/elabftw/issues).

## Requirements
Here is what you need in order to install it :

* a computer running Mac OS X, Windows, or any UNIX-like operating system
* a webserver (apache, cherokee, nginx, lighttpd)
* PHP version > 5.4.3
* MySQL version > 5.1

On Mac OS X, you can install MAMP : www.mamp.info
On Windows, WAMP : www.wampserver.com
On GNU/Linux, well, you know what to do.


Have everything ?
Now, let's begin :

## Php part
The first part is getting the files on the server.

1. Connect to your server with SSH
If you didn't understand this sentence, ask your local geek.

2. Cd to the public directory where you want eLabFTW to be installed
(can be /var/www, ~/public\_html, or any folder you'd like)

3. Get latest stable version via git :
~~~ sh
$ git clone https://github.com/NicolasCARPi/elabftw.git
~~~
(this will create a folder `elabftw`)
If you cannot connect, try exporting your proxy settings in your shell.


## SQL part
The second part is putting the database in place.

### 1) create a user `elabftw` with all rights on the database `elabftw`
I recommend using phpmyadmin for that. Here is the [doc](http://wiki.phpmyadmin.net/pma/user_management).


### 2) import the database structure :
~~~ sh
$ cd elabftw
$ mysql -u elabftw -p elabftw < install/elabftw.sql
~~~

You will be prompted with the password you entered when creating the `elabftw` user in step 1.


## Config file
Copy the file `admin/config-example.ini` to `admin/config.ini` and edit it.
~~~ sh
$ cp admin/config-example.ini admin/config.ini
$ $EDITOR admin/config.ini
~~~

Check that this file isn't served by your webserver (point to it in a browser).

If you see a 403 Error, all is good.

If you see the config file be sure to edit AllowOverride in your <Directory "/var/www/elabftw"> and set it to All.

## Final step
Finally, point your browser to the install folder (install/) and read onscreen instructions.
You can now login with the user `root`, and the password `toor`.

**You MUST change your password asap.**

If you cannot login, check the value of `path` in `admin/config.ini`.


# Updating
To update, just cd in the `elabftw` folder and do :
~~~ sh
$ git pull
$ php update.php
~~~

# Bonus stage
* It's a good idea to use a php optimizer to increase speed. I recommand installing XCache.
* You can show a TODOlist by pressing 't'.
* You can duplicate an experiment in one click.
* You can export in a .zip, a .pdf or a spreadsheet.
* You can share an experiment by just sending the URL of the page to someone else.



~Thank you for using eLabFTW :)

http://www.elabftw.net
