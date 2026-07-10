# Changelog for elabctl

## Version 5.0.4

* Fix an issue where `elabctl update` could fail if no _healthcheck_ was configured for MySQL container. Fix #38 via #39.

## Version 5.0.3

* Properly address #37 by only creating a tmp dir during install or sef-update

## Version 5.0.2

* Cleanup tmp dir created during `self-update` function. fix #37

## Version 5.0.1

* Do a refresh (``docker compose up -d``) instead of a restart after update

## Version 5.0.0

The major version bump is simply to align with the rest of the elabftw related repositories, it has no other meaning.

* Actually check for healthy state of MySQL container rather than wait 15 seconds before running db update. See elabftw/elabftw#4948.

## Version 3.6.4

* Use `mktemp` command to create temporary directory to write temporary files to, without another user being able to read or modify it.

## Version 3.6.3

* Add timeout before update command. See elabftw/elabftw#4948.

## Version 3.6.2

* Prevent issue with unset `BORG_REMOTE_PATH` env. Fix #36.

## Version 3.6.1

* Add `--default-character-set=utf8mb4` to `elabctl mysql` command

## Version 3.6.0

* Allow setting `BORG_REMOTE_PATH` env var for `borg` backup. (fix elabftw/elabftw#4798)

## Version 3.5.0

* Change `bin/console db:install` to new `bin/init db:install`
* Reword the `update` help text

## Version 3.4.0

* Add `DUMP_DELETE_DAYS` variable (defaults to +0) to remove old mysql backup files after generating a dump (fix elabftw/elabftw#4285)

## Version 3.3.0

* Add convenience function `update-db-schema` which will update the MySQL database schema.
* Use new function when running `update`.

## Version 3.2.1

* Improve detection of docker-compose/docker compose command.

## Version 3.2.0

* Remove check for beta version as it could never be true because latest release endpoint of github api doesn't include pre-releases.

## Version 3.1.2

* Don't check initially for docker-compose presence as we can use "docker compose". Instead check for "docker" command presence.

## Version 3.1.1

* Remove initial check for zip and git presence as they are not required

## Version 3.1.0

* Add hours, minutes and seconds to the mysql dump filename

## Version 3.0.0

* Use borg backup for the backup function -> requires change in configuration to work properly!

## Version 2.4.0

* Add convenience function `initialize` to import the database structure
* Populate SITE_URL in elabftw.yml form user input
* Use container names from elabctl.conf in elabftw.yml
* `uninstall` also removes mysql:8.0

## Version 2.3.4

* Remove `--column-statistics=0` to mysqldump command. See https://github.com/elabftw/elabctl/issues/23

## Version 2.3.3

* Add `--column-statistics=0` to mysqldump command.

## Version 2.3.2

* Use `docker compose` for Docker version > 20.x and `docker-compose` otherwise.

## Version 2.3.1

* Use `docker compose` for Docker version > 19.x and `docker-compose` otherwise. (#22)

## Version 2.3.0

* Use `docker compose` instead of `docker-compose` command
* Add a warning with a choice to continue update if the latest version is a beta version

## Version 2.2.4
* Fix ENABLE_LETSENCRYPT being incorrectly set to true for self signed certs (#20)
* Drop "no domain name" support

## Version 2.2.3
* Fix permissions for uploaded files folder chown command

## Version 2.2.2
* Use correct mysql container name for backup

## Version 2.2.1
* Add link to latest version changelog after update

## Version 2.2.0
* Replace php-logs with access-logs and error-logs using docker logs command

## Version 2.1.1
* Fix wrongly committed local config

## Version 2.1.0
* Allow specifying the name of the containers in config file

## Version 2.0.1
* Add --no-tablespaces argument to mysqldump to avoid PROCESS privilege error

## Version 2.0.0

* Get a pre-processed config file from get.elabftw.net
* Don't install certbot or try to get a certificate
* Suppress the MySQL warning. Allow silent backup for cron with > /dev/null
* Add mysql-backup command to just make a dump of the database, don't zip the files

## Version 1.0.4

* Use restart instead of refresh for update command (see elabftw/elabftw#1543)

## Version 1.0.3

* Use refresh instead of restart for update command
* Use certbot instead of letsencrypt-auto

## Version 1.0.2

* Fix bugreport hanging on elabftw version
* Add mysql command to spawn mysql shell in container
* Check for disk space before update (#15)

## Version 1.0.1

* Download conf file to /tmp to avoid permissions issues
* Add sudo for mkdir
* Open port 80 for Let's Encrypt
* Use sudo to remove data dir
* Log file is gone
* Don't try to install stuff, let user deal with it
* Script can be used without being root

## Version 0.6.4

* Fix install on CentOS (thanks @M4aurice) (#14)
* Ask before doing backup

## Version 0.2.2

* Fix install on RHEL (thanks @folf) (#7)
* Fix running backup from cron (#6)
* Use chmod 600 not 700 for config file
* Allow traffic on port 443 with ufw
* Add GPLv3 licence
* Add CHANGELOG.md
