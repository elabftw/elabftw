#!/bin/sh
# little script that tries to figure out what PID/GID the webserver is running.
# 20150209 v0.3 Roger Koot <rkoot at umcutrecht dot nl>
# modified by Nicolas CARPi for elabftw.

# show header because it's cool

echo "       _       _      __ _            "
echo "      | |     | |    / _| |           "
echo "   ___| | __ _| |__ | |_| |___      __"
echo "  / _ \ |/ _' | |_ \|  _| __\ \ /\ / /"
echo " |  __/ | (_| | |_) | | | |_ \ V  V / "
echo "  \___|_|\__,_|_.__/|_|  \__| \_/\_/  "

echo ""
echo "This script attempts to find out proper permissions for your eLabFTW installation."
echo "Nothing will be done to the system, this script only gathers information and shows it to you."
sleep 4

# first thing we want to do is know on which OS we are
# normally there is $OSTYPE = linux-gnu for GNU/Linux
if [ "$OSTYPE" == "linux-gnu" ];then
    # try to get the distrib
    if [ -f /etc/lsb-release ];then
        . /etc/lsb-release
        os=$DISTRIB_ID
        user="www-data"
        group="www-data"
    elif [ -f /etc/debian_version ];then
        os="debian"
        user="www-data"
        group="www-data"
    elif [ -f /etc/redhat-release ];then
        os="redhat/fedora"
        user="apache"
        group="apache"
    elif [ -f /etc/arch-release ];then
        os="archlinux"
        user="http"
        group="http"
    else
        os="linux"
        user="www-data"
        group="www-data"
    fi

elif [ "$OSTYPE" == "darwin"* ];then
    # Mac OSX
    os="macosx"
    user="www-data"
    group="www-data"

elif [ "$OSTYPE" == "freebsd"* ];then
    os="freebsd"
    user="apache24"
    group="apache24"

elif [ "$OSTYPE" == *"bsd"* ];then
    os="bsd"
    user="apache24"
    group="apache24"
else
    os="unknown"
    user=""
    group=""
fi

echo ""
echo "[°] Guessing operating system…"
echo "[°] OS is : $os"
# we assume Apache. If you have something else you should not need this script ;)
echo "[°] Assuming apache as webserver."

# get the full path of the elabftw folder
elab_root=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && cd .. && pwd )

echo "[°] Command to execute:"
echo "================================"
echo "chown -R $user:$group $elab_root"
echo "================================"
echo "[°] It is a good idea to secure the config file also :"
echo "================================"
echo "chmod 644 $elab_root/config.php"
echo "================================"
exit 0
