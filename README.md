![osef](http://i.imgur.com/hq6SAZf.png)


# Description
Please visit the [official website](http://www.elabftw.net) to see some screenshots and use a demo :)

# Installation
Thank you for choosing eLabFTW as a lab manager =)
Please report bugs on [github](https://github.com/NicolasCARPi/elabftw/issues).

eLabFTW was designed to be installed on a server, and people from the team would just log into it from their browser.

Don't have a server ? That's okay, you can use an old computer with 1 Go of RAM and an old CPU, it's more than enough. Just install a recent GNU/Linux distribution on it.

Don't have an old computer ? That's okay, you can install eLabFTW on a Raspberry Pi (you can buy one on [Radiospares](http://www.rs-components.com/index.html)). It's a 30€ computer on which you can install GNU/Linux and run a server in no time ! That's what we use in our lab.

But you can also install it locally and use it for yourself only. Here is how :

### Install locally on Mac OS
Please [follow the instructions on the wiki](https://github.com/NicolasCARPi/elabftw/wiki/installmac).
### Install locally on Windows
Please [follow the instructions on the wiki](https://github.com/NicolasCARPi/elabftw/wiki/installwin).
## Install on Unix-like OS (GNU/Linux, BSD, Solaris, etc…) (the recommended way !)
Please refer to your distribution's documentation to install :
* a webserver (Apache2 is recommended)
* php5
* mysql
* git

The quick way to do that on a Debian/Ubuntu setup :
~~~ sh 
$ sudo apt-get update
$ sudo apt-get upgrade
$ sudo apt-get install mysql-server mysql-client apache2 php5 php5-mysql libapache2-mod-php5 phpmyadmin git
~~~

Make sure to put a root password on your mysql installation :
~~~ sh
$ sudo /usr/bin/mysql_secure_installation
~~~


## Getting the files

### Connect to your server with SSH
~~~ sh
ssh user@12.34.56.78
~~~

### Cd to the public directory where you want eLabFTW to be installed
(can be /var/www, ~/public\_html, or any folder you'd like)
~~~ sh
$ cd /var/www
# make the directory writable by your user
$ sudo chown `whoami`:`whoami` .
~~~
Note the `.` at the end that means `current folder`.

### Get latest stable version via git :
~~~ sh
$ git clone https://github.com/NicolasCARPi/elabftw.git
~~~
(this will create a folder `elabftw`)

If you cannot connect, try exporting your proxy settings in your shell like so :
~~~ sh
$ export https_proxy="proxy.example.com:3128"
~~~
If you still cannot connect, tell git your proxy :
~~~ sh
$ git config --global http.proxy http://proxy.example.com:8080
~~~

If you can't install git or don't manage to get the files, you can [download a zip archive](https://github.com/NicolasCARPi/elabftw/archive/master.zip). But it's better to use git, it will allow easier updates.

### Create the uploads folders and fix the permissions
~~~ sh
$ cd elabftw
$ mkdir -p uploads/{tmp,export}
$ chmod -R 777 uploads
~~~

## SQL part
The second part is putting the database in place.
### Command line way (graphical way below)
~~~ sh
# first we connect to mysql
$ mysql -u root -p
# we create the database (note the ; at the end !)
mysql> create database elabftw;
# we create the user that will connect to the database.
mysql> grant usage on *.* to elabftw@localhost identified by 'YOUR_PASSWORD';
# we give all rights to this user on this database
mysql> grant all privileges on elabftw.* to elabftw@localhost;
mysql> exit
# now we import the database structure
$ mysql -u elabftw -p elabftw < install/elabftw.sql
~~~

*** <- Ignore this (it's to fix a markdown syntax highlighting problem)


### Graphical way with phpmyadmin
#### 1) create a user `elabftw` with all rights on the database `elabftw`
Login with the root user on PhpMyAdmin panel, click the `Users` tab and click Add new user.

Do like this :

![phpmyadmin add user](http://i.imgur.com/kE1gtT1.png)


#### 2) import the database structure :
* On the menu on the left, select the newly created database `elabftw`
* Click the Import tab
* Select the file /path/to/elabftw/install/elabftw.sql
* Click Go

## Config file
Copy the file `admin/config.ini-EXAMPLE` to `admin/config.ini`.
~~~ sh
$ cp admin/config.ini-EXAMPLE admin/config.ini
~~~

Check that this file isn't served by your webserver (point to it in a browser).

If you see a 403 Error, all is good.

If you see the config file be sure to edit AllowOverride in your 
~~~ sh
<Directory "/var/www/elabftw">
~~~ 
in the file `/etc/apache2/conf/httpd.conf` and set it to All.

Reload the webserver :
~~~ sh
# on Debian/Ubuntu
$ sudo service apache2 reload 
# on Archlinux
$ sudo systemctl reload httpd.service
~~~
Now edit this file with nano, a simple text editor. (Use vim/emacs at will, of course !)
~~~ sh
$ nano admin/config.ini
~~~

## Final step
Finally, point your browser to the install folder (install/) and read onscreen instructions.

# Updating
To update, just cd in the `elabftw` folder and do :
~~~ sh
$ git pull
$ php update.php
~~~

# Backup
It is important to backup your files to somewhere else, in case anything bad happens.
Please refer to the [wiki](https://github.com/NicolasCARPi/elabftw/wiki/backup).

# Bonus stage
* It's a good idea to use a php optimizer to increase speed. I recommand installing XCache.
* You can show a TODOlist by pressing 't'.
* You can duplicate an experiment in one click.
* You can export in a .zip, a .pdf or a spreadsheet.
* You can share an experiment by just sending the URL of the page to someone else.


~Thank you for using eLabFTW :)

http://www.elabftw.net

\o/
