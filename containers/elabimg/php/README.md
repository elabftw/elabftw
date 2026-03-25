# PHP configuration files

## Description

This folder contains the configuration files for PHP/PHP-FPM.

Some configuration files contain placeholders (`%EXAMPLE%`) that are replaced by a correct value at runtime (script [prepare.sh](../init/prepare.sh)).

## Files

- `php.ini` is the main configuration file for PHP
- `php-fpm.conf` is the main configuration file for PHP-FPM, it mainly loads the pool file
- `elabpool.conf` is the pool file for an eLabFTW instance
