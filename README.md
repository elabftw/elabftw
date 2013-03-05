![osef](http://i.imgur.com/hq6SAZf.png)


# Description
Please visit the [official website](http://www.elabftw.net) to see some screenshots and use a demo :)

# Installation
Thank you for choosing eLabFTW as a lab manager =)
Please report bugs on [github](https://github.com/NicolasCARPi/elabftw/issues).

eLabFTW was designed to be installed on a server, and people from the team would just log into it from their browser.

But you can also install it locally and use it for yourself only. Here is how :

### Install locally on Mac OS
Please [follow the instructions on the wiki](https://github.com/NicolasCARPi/elabftw/wiki/installmac).
### Install locally on Windows
Please [follow the instructions on the wiki](https://github.com/NicolasCARPi/elabftw/wiki/installwin).
## Install on Unix-like OS (GNU/Linux, BSD, Solaris, etc…)
You can skip these instructions if you already have a running http server.

Please refer to your distribution's documentation to install :
* a webserver (Apache, nginx, cherokee, lighttpd)
* php5
* mysql
* git

## Getting the files

1. Connect to your server with SSH

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
I recommend using phpmyadmin for that.

Login with the root user on PhpMyAdmin panel, click the `Users` tab and click Add new user.

Do like this :

![phpmyadmin add user](http://i.imgur.com/kE1gtT1.png)


### 2) import the database structure :
#### Command line way
~~~ sh
$ cd elabftw
$ mysql -u elabftw -p elabftw < install/elabftw.sql
~~~

You will be prompted with the password you entered when creating the `elabftw` user in step 1.

#### Graphical way (in PhpMyAdmin)
* On the menu on the left, select the newly created database `elabftw`
* Click the Import tab
* Select the file /path/to/elabftw/install/elabftw.sql
* Click Go

## Config file
Copy the file `admin/config.ini-EXAMPLE` to `admin/config.ini` and edit it.
~~~ sh
$ cp admin/config.ini-EXAMPLE admin/config.ini
$ $EDITOR admin/config.ini
~~~

Check that this file isn't served by your webserver (point to it in a browser).

If you see a 403 Error, all is good.

If you see the config file be sure to edit AllowOverride in your <Directory "/var/www/elabftw"> and set it to All.

## Configure upload size
By default, the size might be too small, change the value of `upload_max_filesize` in the `/etc/php/php.ini` file.

## Final step
Finally, point your browser to the install folder (install/) and read onscreen instructions.

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

\o/
