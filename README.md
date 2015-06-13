![elabftw logo](http://i.imgur.com/hq6SAZf.png)

###[Official website](http://www.elabftw.net) | [Live demo](https://demo.elabftw.net)

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/elabftw/elabftw/badges/quality-score.png?b=next)](https://scrutinizer-ci.com/g/elabftw/elabftw/?branch=next)
[![Build Status](https://scrutinizer-ci.com/g/elabftw/elabftw/badges/build.png?b=next)](https://scrutinizer-ci.com/g/elabftw/elabftw/build-status/next)

# Description

**eLabFTW** is an electronic lab notebook manager for research teams. It also features a database where you can store any kind of objects (think antibodies, plasmids, cell lines, boxes, _etc_…)
It is accessed _via_ the browser by the users. Several research teams can be hosted on the same install, so **eLabFTW** can be installed at the institute level and host everyone at the same place (this is what is done at [Institut Curie](http://www.curie.fr) and in several other research centers across the globe.

- Tired of that shared excel file for your antibodies or plasmids ?

- Want to be able to search in your past experiments as easily as you'd do it on google ?

- Want an electronic lab notebook that lets you timestamp legally your experiments ?

- Then you are at the right place !

**eLabFTW** is designed to be installed on a server, and people from the team would just log into it from their browser.
Don't have a server ? That's okay, you can use an old computer with 1 Go of RAM and an old CPU, it's more than enough. Just install a recent GNU/Linux distribution on it and plug it to the intranet.

# Installation
## The legendary four steps installation instructions (for advanced users)
* [Download the latest stable version](https://github.com/elabftw/elabftw/releases/latest/)
* Extract it on your web server
* Create a MySQL database and a MySQL user for elabftw
* Go to https://your-address.org/elabftw/install

## Install on your computer (Mac OS X only)
![Mac OS X](https://i.imgur.com/t62AQAi.png)
[Install locally on Mac](https://github.com/elabftw/elabftw/wiki/installmac)

If you want to install it locally on Windows, have a look at [Boot2Docker](http://boot2docker.io/) and the [docker repo](https://github.com/elabftw/docker-elabftw).
## Install on a digitalocean's drop (easiest/quickest method)
With this method, you can have a running elabftw server in no time. You need to purchase a `drop` from [DigitalOcean.com](https://www.digitalocean.com/pricing/). It starts at 5$/month. This setup is enough to run eLabFTW for a team or more. And it's very easy to install, all is automatic! |
:--------------------------------------------------------------:|
[Install eLabFTW on a drop](https://github.com/elabftw/drop-elabftw#how-to-use) |

## Install in a docker container
![Docker](https://i.imgur.com/VRjbY8R.png) |
:------------------------------------------:|
If you know Docker already and want to use a dockerized elabftw, please see [this repo](https://github.com/elabftw/docker-elabftw). |

## Install on a GNU/Linux or BSD server

![Gnu/Linux](https://i.imgur.com/WkqWf5f.png) ![Beastie](https://i.imgur.com/8vGuEya.png)

Please refer to your distribution's documentation to install :
* a webserver (like nginx, Apache, lighttpd or cherokee)
* php version > 5.4 with the following extensions : gettext, gd, openssl, hash, curl
* mysql version > 5.5
* git

If you don't know how to do that, have a look at [installing eLabFTW on a cheap server (drop)](https://github.com/elabftw/drop-elabftw#how-to-use).

I wouldn't recommend HHVM because Gettext support is not here yet (see [this issue](https://github.com/facebook/hhvm/issues/1228)).

### Getting the files

The first part is to get the files composing `elabftw` on your server, with git.

Alternatively, you can download the latest release from [this page](https://github.com/elabftw/elabftw/releases/latest) as a zip archive or a tarball.

#### Connect to your server with SSH
~~~ sh
ssh user@12.34.56.78
~~~

#### Cd to the public directory where you want eLabFTW to be installed
(can be /var/www, ~/public\_html, or any folder you'd like, as long as the webserver is configured properly, in doubt use /var/www)
~~~ sh
$ cd /var/www
# make the directory writable by your user (if it's not already the case)
$ sudo chown `whoami`:`whoami` .
~~~
Note the `.` at the end that means `current folder`.

#### Get latest stable version via git :
~~~ sh
$ git clone --depth 1 https://github.com/elabftw/elabftw.git
~~~
(this will create a folder `elabftw`)
The `--depth 1` option is to avoid downloading the whole history.

If you cannot connect, try exporting your proxy settings in your shell like so :
~~~ sh
$ export https_proxy="proxy.example.com:3128"
~~~
If you still cannot connect, tell git your proxy :
~~~ sh
$ git config --global http.proxy http://proxy.example.com:8080
~~~

Alternatively, you can [download a stable version](https://github.com/elabftw/elabftw/releases/latest).

But git will allow for easier updates (and they are frequent !).

### SQL part
The second part is putting the database in place.
#### Command line way (graphical way below)
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
~~~
You will be asked for the password you put after `identified by` three lines above.

*<- Ignore this (it's to fix a markdown syntax highlighting problem)


#### Graphical way with phpmyadmin
You need to install the package `phpmyadmin` if it's not already done.

**Note**: it is not recommended to have phpmyadmin installed on a production server (for security reasons).

~~~sh
$ sudo apt-get install phpmyadmin
~~~

Now you will connect to the phpmyadmin panel from your browser on your computer. Type the IP address of the server followed by /phpmyadmin.

Example : https://12.34.56.78/phpmyadmin

Login with the root user on PhpMyAdmin panel (use the password you setup for mysql root user).
##### Create a user `elabftw` with all rights on the database `elabftw`

Now click the `Users` tab and click ![add user](http://i.imgur.com/SJmdg0Z.png).

Do like this :

![phpmyadmin add user](http://i.imgur.com/kE1gtT1.png)


### Final step
Finally, point your browser to the install folder (install/) and read onscreen instructions.

For example : https://12.34.56.78/elabftw/install

-------------------------------------------------

# Post install things to do
You should read [this page](https://github.com/elabftw/elabftw/wiki/finalizing) to finish your install (configure email, backup, *etc*…).

-------------------------------------------------

Please report bugs on [github](https://github.com/elabftw/elabftw/issues).

~Thank you for using [eLabFTW](http://www.elabftw.net) :)
