#!/usr/bin/env bash
# this script can be used to reload nginx or php
# call it without arguments to reload both
# or with 'php or 'nginx' as argument to reload this service

function reload {
    echo "Reloading $1 service..."
    /package/admin/s6/command/s6-svc -r /run/service/"$1"
    echo "$1 reloaded"
}

if [ "$#" -eq 0 ]; then
    reload nginx
    reload php
elif [ "$1" == "nginx" ]; then
    reload nginx
elif [ "$1" == "php" ]; then
    reload php
else
    echo "ERROR: Invalid argument. Use 'nginx' or 'php', or no arguments."
    exit 1
fi
