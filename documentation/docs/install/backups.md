---
sidebar_position: 6
title: Backups
---

# How to Backup

## Introduction

This page documents how to backup an existing eLabFTW installation. It is important that you take the time to make sure that your backups are working properly.

![Did you backup?](/img/didyoubackup.webp)

There are basically three things to backup:

- The MySQL database (by default in `/var/elabftw/mysql`) => handled by `elabctl` through `mysqldump`
- The uploaded files (by default in `/var/elabftw/web`) => handled by `elabctl` through `borgbackup`
- Your configuration file (by default `/etc/elabftw.yml`) => **NOT** handled by `elabctl` on purpose, read below

Important note: the configuration file is **NOT** backed up along with the data. This is done to avoid bundling secrets (such as the `SECRET_KEY` bundled with the data). You are responsible for backing up your configuration values (such as database password/IP/bind-mounted folders/ENV vars). This is kept separate on purpose. We highly recommend using configuration management tools such as `ansible` to manage this aspect.

Note that in the event where you lose your configuration values, this will not impact your data. Only the SMTP password and optional TSA password will need to be re-encrypted with a new `SECRET_KEY`. And when redeploying, you will use a different `DB_PASSWORD`, but this has no impact on the actual data. As such, configuration values are not critical to the integrity of your backups.

## How to backup a Docker installation

### Important note

The instructions below are merely a suggestion on how to proceed. If you are familiar with different tools or procedures to backup data, use them. At the end of the day, eLabFTW's data is a very classical MySQL database and even more classical files. The important points are:

- Another point is: you **never** have too many backups. So use a full VM backup + mysqldump + copy files here and there + do a filesystem snapshot. Use all the tools at your disposal. Read this *postmortem* about a [Gitlab.com outage and how they discovered how broken their backup procedures were](https://about.gitlab.com/blog/2017/02/10/postmortem-of-database-outage-of-january-31).

### With elabctl

Using the backup function of `elabctl` is the recommended approach. The MySQL database will be dumped thanks to `mysqldump` present in the `mysql` container. The uploaded files will be copied with [borgbackup](https://www.borgbackup.org/) and you need to install it first and then configure it.

#### Configuration

Start by figuring out where you want the borg repository to live. It can be local or remote folder (remote is better but requires ssh correctly setup to access it). It can also be local but on a network-mounted path, which makes it remote.

After installing borg, initialize a new repository with:

~~~bash
# for a local path
borg init -e repokey-blake2 /path/to/elabftw-borg-repo
# for a remote (ssh) path
borg init -e repokey-blake2 someserver:/path/to/elabftw-borg-repo
~~~

It is necessary to use the `elabctl.conf` configuration file (available [here](https://raw.githubusercontent.com/elabftw/elabctl/master/elabctl.conf)). Place this file in `/root/.config/elabctl.conf` and make sure to specify the settings correctly.


#### Test

Try the backup with:

~~~bash
elabctl backup
~~~

You can also use `mysql-backup` to only backup the MySQL database:

~~~bash
elabctl mysql-backup
~~~

You can also use `borg-backup` to only backup the uploaded files:

~~~bash
elabctl borg-backup
~~~

::::warning
Important: verify that all the files are correctly created and that you will be able to restore from a backup!
::::

### Without elabctl

You're on your own. Use your favorite tools to backup the MySQL database and uploaded files.

## Making it automatic using cron

A good backup is automatic. Use a cronjob or a systemd timer job to trigger the backup job regularly (ideally daily).

### With a cronjob

If you have the traditional cron service running, try::

~~~bash
crontab -e
~~~

This will open the cronjob file in edit mode.

Add this line at the bottom::

~~~cron
00 04 * * * /path/to/elabctl backup
~~~

This will run the script everyday at 4am. Make sure to write the full path to `elabctl` as it might not be in the `$PATH` for cron.

### With a systemd timer

Some systems don't use the traditional cron service, so instead of installing it, you should use a systemd timer (provided systemd is your init system, which is quite likely).

You will need to create two files, one .service and one .timer.

Content of `/etc/systemd/system/elabftw-backup.service`:

~~~ini title="/etc/systemd/system/elabftw-backup.service"
[Unit]
Description=Backup eLabFTW data

[Service]
Type=oneshot
ExecStart=/path/to/elabctl backup
# Make sure to use a user with enough rights
User=root

[Install]
WantedBy=multi-user.target
~~~

Content of `/etc/systemd/system/elabftw-backup.timer`:

~~~ini title="/etc/systemd/system/elabftw-backup.timer"
[Unit]
Description=Backup eLabFTW data

[Timer]
OnCalendar=*-*-* 4:00:00
Persistent=true

[Install]
WantedBy=timers.target
~~~

Now activate it:

~~~bash
systemctl enable elabftw-backup
systemctl start elabftw-backup
~~~

## How to restore a backup

You should have three files/folders to start with:

- MySQL dump
- Uploaded files
- Configuration file

To extract your uploaded files from a borg backup:

~~~bash
export BORG_REPO=/path/to/borg/repo
export BORG_PASSPHRASE="your passphrase"
borg list
borg extract "::example-2022-07-14_13-37"
~~~

See documentation on how to manage your borg repository: [Borg extract documentation](https://borgbackup.readthedocs.io/en/stable/usage/extract.html).

Then we move the uploaded files and config file at the correct place (adjust the paths to your case):

~~~bash
mv /path/to/uploaded-files-backup/* /var/elabftw/web
mv /path/to/configuration-backup-elabftw.yml /etc/elabftw.yml
# now fix the permissions
chown -R 101:101 /var/elabftw/web
chmod 600 /etc/elabftw.yml
~~~

Now we import the SQL database (the mysql container must be running):

~~~bash
gunzip mysql_dump-YYYY-MM-DD.sql.gz # uncompress the file
docker cp mysql_dump-YYYY-MM-DD.sql mysql:/ # copy it inside the mysql container
docker exec -it mysql bash # spawn a shell in the mysql container
mysql -uroot -p$MYSQL_ROOT_PASSWORD # login to mysql prompt
~~~

~~~sql
Mysql> drop database elabftw; # delete the brand new database
Mysql> create database elabftw character set utf8mb4 collate utf8mb4_0900_ai_ci; # create a new one
Mysql> use elabftw; # select it
Mysql> set names utf8mb4; # make sure you import in utf8 (don't do this if you are in latin1)
Mysql> source mysql_dump-YYYY-MM-DD.sql; # import the backup
Mysql> exit;
~~~

Now you should have your old install back :)
