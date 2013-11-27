![elabftw logo](http://i.imgur.com/hq6SAZf.png)


# Description
eLabFTW is an electronic lab notebook manager for research teams. It also features a database where you can store any kind of objects.

* [Official website](http://www.elabftw.net)
* [Live demo](http://demo.elabftw.net)

# Installation
Thank you for choosing eLabFTW as a lab manager =)
Please report bugs on [github](https://github.com/NicolasCARPi/elabftw/issues).

eLabFTW was designed to be installed on a server, and people from the team would just log into it from their browser.

Don't have a server ? That's okay, you can use an old computer with 1 Go of RAM and an old CPU, it's more than enough. Just install a recent GNU/Linux distribution on it.

Don't have an old computer ? That's okay, you can install eLabFTW on a Raspberry Pi (you can buy one on [Radiospares](http://www.rs-components.com/index.html)). It's a 30€ computer on which you can install GNU/Linux and run a server in no time ! That's what we use in our lab.

But you can also install it locally and use it for yourself only. Here is how :

* [Install locally on Mac](https://github.com/NicolasCARPi/elabftw/wiki/installmac).
* [Install locally on Windows](https://github.com/NicolasCARPi/elabftw/wiki/installwin).

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
$ mysql -u elabftw -p elabftw < elabftw/install/elabftw.sql
~~~
You will be asked for the password you put after `identified by` three lines above.

*<- Ignore this (it's to fix a markdown syntax highlighting problem)


### Graphical way with phpmyadmin
You need to install the package `phpmyadmin` if it's not already done.

~~~sh
$ sudo apt-get install phpmyadmin
~~~

Now you will connect to the phpmyadmin panel from your browser on your computer. Type the IP address of the server followed by /phpmyadmin.

Example : http://12.34.56.78/phpmyadmin

Login with the root user on PhpMyAdmin panel (use the password you setup for mysql root user).
#### 1) create a user `elabftw` with all rights on the database `elabftw`

Now click the `Privileges` tab and click Add new user.

Do like this :

![phpmyadmin add user](http://i.imgur.com/kE1gtT1.png)


#### 2) import the database structure :
* On the menu on the left, select the newly created database `elabftw`
* Click the Import tab
* Download [this file](https://raw.github.com/NicolasCARPi/elabftw/master/install/elabftw.sql)
* Click Browse... and select the file you just downloaded
* Click Go

## Final step
Finally, point your browser to the install folder (install/) and read onscreen instructions.

For example : http://12.34.56.78/elabftw/install

******

# Updating
To update, just cd in the `elabftw` folder and do :
~~~ sh
$ git pull
$ php update.php
~~~

# Backup
It is important to backup your files to somewhere else, in case anything bad happens.
Please refer to the [wiki](https://github.com/NicolasCARPi/elabftw/wiki/backup).

# HTTPS
If you want to enable HTTPS (and you should), uncomment (remove the # at the beginning) these lines in the file .htaccess. 
~~~sh
#RewriteEngine On
#RewriteCond %{HTTPS} !=on
#RewriteRule .* https://%{SERVER_NAME}%{REQUEST_URI} [R=301,L]
~~~

You will need the modules "rewrite" and "ssl" enabled, the package ssl-cert installed and the ssl site enabled.
~~~sh
$ sudo apt-get install ssl-cert
$ sudo a2enmod rewrite
$ sudo a2enmod ssl
$ sudo a2ensite default-ssl
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
