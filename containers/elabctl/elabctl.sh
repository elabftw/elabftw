#!/usr/bin/env bash
# https://www.elabftw.net
# https://github.com/elabftw/elabctl/
# © 2022 Nicolas CARPi @ Deltablot
# License: GPLv3
declare -r ELABCTL_VERSION='5.1.0'

# default backup dir
declare BACKUP_DIR='/var/backups/elabftw'

# defines the time until older mysql dumps will be deleted (+0 = older than 24h, +1 = older than 48h and so on)
declare DUMP_DELETE_DAYS=+0

# default config file for docker compose
declare CONF_FILE='/etc/elabftw.yml'
# default data directory
declare DATA_DIR='/var/elabftw'
# allow override default web subfolder in data dir
declare UPLOAD_DIR="${DATA_DIR}/web"

# default conf file is no conf file
declare ELABCTL_CONF_FILE="using default values (no config file found)"

# use the new compose command
declare DC="docker compose"

function access-logs
{
    docker logs "${ELAB_WEB_CONTAINER_NAME}" 2>/dev/null
}

# display ascii logo
function ascii
{
    echo ""
    echo "      _          _     _____ _______        __"
    echo "  ___| |    __ _| |__ |  ___|_   _\ \      / /"
    echo " / _ \ |   / _| | '_ \| |_    | |  \ \ /\ / / "
    echo "|  __/ |__| (_| | |_) |  _|   | |   \ V  V /  "
    echo " \___|_____\__,_|_.__/|_|     |_|    \_/\_/   "
    echo "                                              "
    echo ""
}

# create a mysqldump and a borg snapshot of the uploaded files
function backup
{
    mysql-backup
    borg-backup
}

function borg-backup
{
    set -eu
    # add these into env so it is picked up by borg
    export BORG_REPO="${BORG_REPO}"
    if [[ -v BORG_PASSPHRASE ]]; then
        export BORG_PASSPHRASE="${BORG_PASSPHRASE}"
    fi
    if [[ -v BORG_PASSCOMMAND ]]; then
        export BORG_PASSCOMMAND="${BORG_PASSCOMMAND}"
    fi
    if [[ -v BORG_REMOTE_PATH ]]; then
        export BORG_REMOTE_PATH="${BORG_REMOTE_PATH}"
    fi
    # we add to the borg the uploaded files (web directory) and also the backup dir containing dumps of MySQL
    "${BORG_PATH}" create "::$(hostname)-$(date +%F_%H-%M)" "${UPLOAD_DIR}" "${BACKUP_DIR}"
    "${BORG_PATH}" prune --keep-daily="${BORG_KEEP_DAILY:-14}" --keep-monthly="${BORG_KEEP_MONTHLY:-6}"
}

# generate info for reporting a bug
function bugreport
{
    echo "Collecting information for a bug report…"
    echo "======================================================="
    echo -n "Elabctl version: "
    echo $ELABCTL_VERSION
    echo -n "Elabftw version: see on sysconfig page"
    echo "======================================================="
    echo -n "Docker version: "
    docker version | grep -m 1 Version | awk '{print $2}'
    echo "======================================================="
    echo "Operating system: "
    uname -a
    cat /etc/os-release
    echo "======================================================="
    echo "Memory:"
    free -h
    echo "======================================================="
}

function checkDeps
{
    need_to_quit=0

    for bin in dialog docker curl sudo
    do
        if ! hash "$bin" 2>/dev/null; then
            echo "Error: $bin not found in the \$PATH. Please install the program '$bin' or fix your \$PATH."
            need_to_quit=1
        fi
    done

    if [ $need_to_quit -eq 1 ]; then
        exit 1
    fi

    require_docker_compose
}

function error-logs
{
    docker logs "${ELAB_WEB_CONTAINER_NAME}" 1>/dev/null
}

function get-user-conf
{
    # download the config file in the current directory
    echo "Downloading the config file 'elabctl.conf' in current directory..."
    if [ -f elabctl.conf ]; then
        mv -v elabctl.conf elabctl.conf.old
    fi
    curl -Ls https://github.com/elabftw/elabctl/raw/master/elabctl.conf -o elabctl.conf
    echo "Downloaded elabctl.conf."
    echo "Edit it and move it in ~/.config or /etc."
    echo "Or leave it there and always use elabctl from this directory."
    echo "Then do 'elabctl install' again."
}

function has-disk-space
{
    # check if we have enough space on disk to update the docker image
    docker_folder=$(docker info --format '{{.DockerRootDir}}')
    # use default if previous command didn't work
    safe_folder=${docker_folder:-/var/lib/docker}
    space_test=$(($(stat -f --format="%a*%S" "$safe_folder")/1024**3 < 5))
    if [[ $space_test -ne 0 ]]; then
        echo "ERROR: There is less than 5 Gb of free space available on the disk where $safe_folder is located!"
        df -h "$safe_folder"
        echo ""
        read -p "Remove old images and containers to free up some space? (y/N)" -n 1 -r
        echo ""
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            docker system prune
        fi
        exit 1
    fi
}

function help
{
    version
    echo "
    Usage: elabctl [OPTION] [COMMAND]
           elabctl [ --help | --version ]
           elabctl install
           elabctl backup

    Commands:

        access-logs         Show last lines of webserver access log
        backup              Backup your installation
        borg-backup         Backup the files with borgbackup
        bugreport           Gather information about the system for a bug report
        error-logs          Show last lines of webserver error log
        help                Show this text
        info                Display the configuration variables and status
        initialize          Initialize the MySQL database for eLabFTW
        install             Configure and install required components
        logs                Show logs of the containers
        mysql               Open a MySQL prompt in the 'mysql' container
        mysql-backup        Make a MySQL dump file for backup
        refresh             Recreate the containers if they need to be
        restart             Restart the containers
        self-update         Update the elabctl script
        status              Show status of running containers
        start               Start the containers
        stop                Stop the containers
        uninstall           Uninstall eLabFTW and purge data
        update              Pull the image defined, restart containers and update database schema
        update-db-schema    Update the MySQL database schema
        version             Display elabctl version
    "
}

function info
{
    echo "Backup directory: ${BACKUP_DIR}"
    echo "Data directory: ${DATA_DIR}"
    echo "Upload directory: ${UPLOAD_DIR}"
    echo "Web container name: ${ELAB_WEB_CONTAINER_NAME}"
    echo "MySQL container name: ${ELAB_MYSQL_CONTAINER_NAME}"
    echo ""
    echo "Status:"
    status
}

function initialize
{
    is-installed
    docker exec -it "${ELAB_WEB_CONTAINER_NAME}" bin/init db:install
}

# get elabftw.yml and configure it with sed
function install
{
    checkDeps

    # do nothing if there are files in there
    if [ "$(ls -A $DATA_DIR 2>/dev/null)" ]; then
        echo "It looks like eLabFTW is already installed. Delete the ${DATA_DIR} folder to reinstall."
        exit 1
    fi

    # init vars
    # if you don't want any dialog
    declare unattended=${ELAB_UNATTENDED:-0}
    declare servername=${ELAB_SERVERNAME:-localhost}
    declare hasdomain=${ELAB_HASDOMAIN:-0}
    declare usehttps=${ELAB_USEHTTPS:-1}
    declare useselfsigned=${ELAB_USESELFSIGNED:-0}

    # exit on error
    set -e

    title="Install eLabFTW"
    backtitle="eLabFTW installation"

    # show welcome screen and ask if defaults are fine
    if [ "$unattended" -eq 0 ]; then
        # because answering No to dialog equals exit != 0
        set +e

        # welcome screen
        dialog --backtitle "$backtitle" --title "$title" --msgbox "\nWelcome to the install of eLabFTW :)\n
        This script will automatically install eLabFTW in a Docker container." 0 0

        dialog --colors --backtitle "$backtitle" --title "$title" --yes-label "Looks good to me" --no-label "Download example conf and quit" --yesno "\nHere is what will happen:\n
        The main configuration file will be created at: \Z4${CONF_FILE}\Zn\n
        A directory holding elabftw MySQL data will be created at: \Z4${DATA_DIR}/mysql\Zn\n
        A directory holding elabftw uploaded files will be created at: \Z4${UPLOAD_DIR}\Zn\n
        The backups will be created at: \Z4${BACKUP_DIR}\Zn\n\n
        If you wish to change these settings, quit now and edit the file \Z4elabctl.conf\Zn" 0 0
        if [ $? -eq 1 ]; then
            get-user-conf
            exit 0
        fi
    fi

    # create the data dir
    mkdir -pv $DATA_DIR
    if [ $? -eq 1 ]; then
        sudo mkdir -pv $DATA_DIR
    fi

    declare TMP_DIR=$(mktemp -d)
    declare TMP_CONF_FILE="${TMP_DIR}/elabftw.yml"

    if [ "$unattended" -eq 0 ]; then
        set +e
        ########################################################################
        # start asking questions                                               #
        # what we want here is the domain name of the server or its IP address #
        # and also if we want to use Let's Encrypt or not
        ########################################################################

        # ASK SERVER OR LOCAL?
        dialog --backtitle "$backtitle" --title "$title" --yes-label "Server" --no-label "My computer" --yesno "\nAre you installing it on a Server or a personal computer?" 0 0
        if [ $? -eq 1 ]; then
            # local computer
            servername="localhost"
        else
            # server

            ## DOMAIN NAME OR IP BLOCK
            dialog --backtitle "$backtitle" --title "$title" --yesno "\nIs a domain name pointing to this server?\n\nAnswer yes if this server can be reached using a domain name. Answer no if you can only reach it with an IP address.\n" 0 0
            if [ $? -eq 0 ]; then
                hasdomain=1
                # ask for domain name
                servername=$(dialog --backtitle "$backtitle" --title "$title" --inputbox "\nPlease enter your domain name below:\nExample: elabftw.example.org\n" 0 0 --output-fd 1)
            else
                # no domain is not supported, exit
                dialog --backtitle "$backtitle" --title "$title" --msgbox "\nInstallation without a proper domain name is not supported.\n" 0 0
                exit 1
            fi
            ## END DOMAIN NAME OR IP BLOCK

            # ASK IF WE WANT HTTPS AT ALL FIRST
            dialog --backtitle "$backtitle" --title "$title" --yes-label "Use HTTPS" --no-label "Disable HTTPS" --yesno "\nDo you want to run the HTTPS enabled container or a normal HTTP server? Note: disabling HTTPS means you will use another webserver as a proxy for TLS connections.\n\nChoose 'Disable HTTPS' if you already have a webserver capable of terminating TLS requests running (Apache/Nginx/HAProxy).\nChoose 'Use HTTPS' if unsure.\n" 0 0
            if [ $? -eq 1 ]; then
                # use HTTP
                usehttps=0
            else
                if [ $hasdomain -eq 1 ]; then
                    # ASK IF SELF-SIGNED OR PROPER CERT
                    dialog --backtitle "$backtitle" --title "$title" --yes-label "Use correct certificate" --no-label "Use self-signed" --yesno "\nDo you want to use a proper TLS certificate (coming from Let's Encrypt or provided by you) or use a self-signed certificate? The self-signed certificate will be automatically generated for you, but browsers will display a warning when connecting.\n\nChoose 'Use self-signed' if you do not have a domain name.\n" 0 0
                    if [ $? -eq 0 ]; then
                        # want correct cert
                        dialog --backtitle "$backtitle" --title "$title" --msgbox "\nSee the documentation on how to configure your TLS certificate before starting the containers.\n" 0 0
                    else
                        # use self signed
                        useselfsigned=1
                        dialog --backtitle "$backtitle" --title "$title" --msgbox "\nA self-signed certificate will be generated upon container start. But really you should try and use a domain name ;)\n" 0 0
                    fi
                fi
            fi
        fi
    fi



    set -e

    echo 40 | dialog --backtitle "$backtitle" --title "$title" --gauge "Creating folder structure. You will be asked for your password (bottom left of the screen)." 20 80
    sudo mkdir -pv ${DATA_DIR}/mysql ${UPLOAD_DIR}
    sudo chmod -Rv 700 ${DATA_DIR} ${UPLOAD_DIR}
    echo "Executing: sudo chown -v 999:999 ${DATA_DIR}/mysql"
    sudo chown -v 999:999 ${DATA_DIR}/mysql
    echo "Executing: sudo chown -v 101:101 ${UPLOAD_DIR}"
    sudo chown -v 101:101 ${UPLOAD_DIR}
    sleep 2

    echo 50 | dialog --backtitle "$backtitle" --title "$title" --gauge "Grabbing the docker compose configuration file" 20 80
    # make a copy of an existing conf file
    if [ -e $CONF_FILE ]; then
        echo 55 | dialog --backtitle "$backtitle" --title "$title" --gauge "Making a copy of the existing configuration file." 20 80
        \cp $CONF_FILE ${CONF_FILE}.old
    fi

    # get a config file already filled with random passwords/keys
    curl --silent "https://get.elabftw.net/?config" -o "$TMP_CONF_FILE"
    sleep 1

    # elab config
    echo 50 | dialog --backtitle "$backtitle" --title "$title" --gauge "Adjusting configuration" 20 80
    sed -i -e "s/SERVER_NAME=localhost/SERVER_NAME=$servername/" $TMP_CONF_FILE
    sed -i -e "s:/var/elabftw/web:${UPLOAD_DIR}:" $TMP_CONF_FILE
    sed -i -e "s/container_name: elabftw/container_name: ${ELAB_WEB_CONTAINER_NAME}/" $TMP_CONF_FILE
    sed -i -e "s/container_name: mysql/container_name: ${ELAB_MYSQL_CONTAINER_NAME}/" $TMP_CONF_FILE

    # disable https
    scheme="https://"
    if [ $usehttps -eq 0 ]; then
        sed -i -e "s/DISABLE_HTTPS=false/DISABLE_HTTPS=true/" $TMP_CONF_FILE
        scheme="http://"
    fi

    # enable letsencrypt
    if [ $hasdomain -eq 1 ] && [ $useselfsigned -eq 0 ]; then
        # even if we don't use Let's Encrypt, for using TLS certs we need this to be true, and volume mounted
        sed -i -e "s:ENABLE_LETSENCRYPT=false:ENABLE_LETSENCRYPT=true:" $TMP_CONF_FILE
        sed -i -e "s:#- /etc/letsencrypt:- /etc/letsencrypt:" $TMP_CONF_FILE
    fi

    sed -i -e "s#SITE_URL=#SITE_URL=$scheme$servername#" $TMP_CONF_FILE

    sleep 1

    # setup restrictive permissions
    chmod 600 "$TMP_CONF_FILE"

    # now move conf file at proper location
    # use sudo in case it's in /etc and we are not root
    sudo mv "$TMP_CONF_FILE" "$CONF_FILE"

    rmdir -v ${TMP_DIR}

    # final screen
    if [ "$unattended" -eq 0 ]; then
        dialog --colors --backtitle "$backtitle" --title "Installation finished" --msgbox "\nCongratulations, eLabFTW was successfully installed! :)\n\n
        \Z1====>\Zn Finish the installation by configuring TLS certificates.\n\n
        \Z1====>\Zn Then start the containers with: \Zb\Z4elabctl start\Zn\n\n
        \Z1====>\Zn Then import the database structure with: \Zb\Z4elabctl initialize\Zn\n\n
        \Z1====>\Zn Go to https://$servername once started!\n\n
        In the mean time, check out what to do after an install:\n
        \Z1====>\Zn https://doc.elabftw.net/sysadmin-guide.html#setting-up-email\n\n
        The configuration file for docker compose is here: \Z4$CONF_FILE\Zn\n
        Your data folder is: \Z4${DATA_DIR}\Zn. It contains the MySQL database and uploaded files.\n
        You can use 'docker logs -f ${ELAB_WEB_CONTAINER_NAME}' to follow the starting up of the container.\n" 20 80
    fi

}

function is-installed
{
    if [ ! -f $CONF_FILE ]; then
        echo "###### ERROR ##########################################################"
        echo "Configuration file (${CONF_FILE}) could not be found!"
        echo "Did you run the install command?"
        echo "#######################################################################"
        exit 1
    fi
}

function logs
{
    docker logs "${ELAB_MYSQL_CONTAINER_NAME}"
    docker logs "${ELAB_WEB_CONTAINER_NAME}"
}

function mysql
{
    docker exec -it "${ELAB_MYSQL_CONTAINER_NAME}" bash -c 'mysql -u$MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE --default-character-set=utf8mb4'
}

# create a mysqldump and remove old backups
function mysql-backup
{
    if ! ls -A "${BACKUP_DIR}" > /dev/null 2>&1; then
        mkdir -pv "${BACKUP_DIR}"
        if [ $? -eq 1 ]; then
            sudo mkdir -pv ${BACKUP_DIR}
        fi
    fi

    set -e

    # get clean date
    local -r date=$(date +%Y-%m-%d_%H-%M-%S) # 2016-02-10_20-12-45
    local -r dumpfile="${BACKUP_DIR}/mysql_dump-${date}.sql"

    # dump sql
    docker exec "${ELAB_MYSQL_CONTAINER_NAME}" bash -c 'mysqldump -u$MYSQL_USER -p$MYSQL_PASSWORD -r dump.sql --no-tablespaces $MYSQL_DATABASE 2>&1 | grep -v "Warning: Using a password"' || echo ">> Containers must be running to do the backup!"
    # copy it from the container to the host
    docker cp "${ELAB_MYSQL_CONTAINER_NAME}:dump.sql" "$dumpfile"
    # compress it to the max
    gzip -f --best "$dumpfile"
    # delete old dumps
    if [[ "${DUMP_DELETE_DAYS}" != "disabled" ]]; then
        find ${BACKUP_DIR} -mindepth 1 -name '*.sql.gz' -ctime ${DUMP_DELETE_DAYS} -delete
    fi
}

function refresh
{
    start
}

function require_docker_compose {
  if docker compose version >/dev/null 2>&1; then
    return 0
  fi

  echo "ERROR: 'docker compose' is not available (or Docker is not running)." >&2
  exit 1
}

function restart
{
    stop
    start
}

function self-update
{
    me=$(command -v "$0")
    TMP_DIR=$(mktemp -d)
    tmp_filepath="${TMP_DIR}/elabctl"
    echo "Downloading new version to $tmp_filepath"
    curl -sL https://raw.githubusercontent.com/elabftw/elabctl/master/elabctl.sh -o "$tmp_filepath"
    chmod -v +x "$tmp_filepath"
    mv -v "$tmp_filepath" "$me"
    rmdir -v ${TMP_DIR}
}

function start
{
    is-installed
    eval "$DC" -f "$CONF_FILE" up -d
}

function status
{
    is-installed
    eval "$DC" -f "$CONF_FILE" ps
}

function stop
{
    is-installed
    eval "$DC" -f "$CONF_FILE" down
}

function uninstall
{
    stop

    local -r backtitle="eLabFTW uninstall"
    local title="Uninstall"

    set +e

    dialog --backtitle "$backtitle" --title "$title" --yesno "\nWarning! You are about to delete everything related to eLabFTW on this computer!\n\nThere is no 'go back' button. Are you sure you want to do this?\n" 0 0
    if [ $? != 0 ]; then
        exit 1
    fi

    dialog --backtitle "$backtitle" --title "$title" --yesno "\nDo you want to delete the backups, too?" 0 0
    if [ $? -eq 0 ]; then
        rmbackup='y'
    else
        rmbackup='n'
    fi

    dialog --backtitle "$backtitle" --title "$title" --ok-label "Skip timer" --cancel-label "Cancel uninstall" --pause "\nRemoving everything in 10 seconds. Stop now you fool!\n" 20 40 10
    if [ $? != 0 ]; then
        exit 1
    fi

    clear

    # remove config file and eventual backup
    if [ -f "${CONF_FILE}.old" ]; then
        rm -vf "${CONF_FILE}.old"
        echo "[x] Deleted ${CONF_FILE}.old"
    fi
    if [ -f "$CONF_FILE" ]; then
        rm -vf "$CONF_FILE"
        echo "[x] Deleted $CONF_FILE"
    fi
    # remove uploads directory
    if [ -d "$UPLOAD_DIR" ]; then
        sudo rm -rvf "$UPLOAD_DIR"
        echo "[x] Deleted $UPLOAD_DIR"
    fi
    # remove data directory
    if [ -d "$DATA_DIR" ]; then
        sudo rm -rvf "$DATA_DIR"
        echo "[x] Deleted $DATA_DIR"
    fi
    # remove backup dir
    if [ $rmbackup == 'y' ] && [ -d "$BACKUP_DIR" ]; then
        rm -rvf "$BACKUP_DIR"
        echo "[x] Deleted $BACKUP_DIR"
    fi

    # remove docker images
    docker rmi elabftw/elabimg || true
    docker rmi mysql:5.7 || true
    docker rmi mysql:8.0 || true

    echo ""
    echo "[✓] Everything has been obliterated. Have a nice day :)"
}

function update
{
    is-installed
    has-disk-space
    echo "Do you want to make a backup before updating? (y/N)"
    read -r dobackup
    if [ "$dobackup" = "y" ]; then
        backup
        echo "Backup done, now updating."
    fi
    eval "$DC" -f "$CONF_FILE" pull
    refresh

    echo "Do you want to update the MySQL database schema? (recommended) (y/N)"
    read -r doDbUpdate
    if [ "$doDbUpdate" = "y" ]; then
        update-db-schema
    fi

    echo "You are now running the latest eLabFTW version."
    echo "Make sure to read the CHANGELOG!"
    echo "=> https://github.com/elabftw/elabftw/releases/latest"
}

function update-db-schema
{
    is-installed
    # wait for mysql container to start, but only if there is one
    if docker ps | grep -q "${ELAB_MYSQL_CONTAINER_NAME}"; then
        echo -n "Waiting for the MySQL container to be ready before running update..."
        while true; do
            # check if healthcheck is available or else will crash (e.g. with older versions of elabFTW config files)
            health_status=$(docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}no-healthcheck{{end}}' "${ELAB_MYSQL_CONTAINER_NAME}")
            if [ "$health_status" == "healthy" ]; then
                break
            fi
            if [ "$health_status" == "no-healthcheck" ]; then
                echo -e "\nNo healthcheck found. Waiting for 20 seconds as fallback..."
                sleep 20
                break
            fi
            # wait and retry while showing user activity
            echo -n .
            sleep 2
        done
    fi
    echo "Running command 'bin/console db:update' in the eLabFTW container now"
    docker exec -it "${ELAB_WEB_CONTAINER_NAME}" bin/console db:update
}

function upgrade
{
    update
}

function usage
{
    help
}

function version
{
    echo "elabctl © 2017 Nicolas CARPi - https://www.elabftw.net"
    echo "elabctl version: $ELABCTL_VERSION"
}

# SCRIPT BEGIN

# only one argument allowed
if [ $# != 1 ]; then
    help
    exit 1
fi

# deal with --help and --version
case "$1" in
    -h|--help)
    help
    exit 0
    ;;
    -v|--version)
    version
    exit 0
    ;;
esac

# default settings that could be overridden by config

declare ELAB_WEB_CONTAINER_NAME='elabftw'
declare ELAB_MYSQL_CONTAINER_NAME='mysql'

# Now we load the configuration file for custom directories set by user
if [ -f /etc/elabctl.conf ]; then
    source /etc/elabctl.conf
    ELABCTL_CONF_FILE="/etc/elabctl.conf"
fi

# elabctl.conf in ~/.config
if [ -f "${HOME}/.config/elabctl.conf" ]; then
    source "${HOME}/.config/elabctl.conf"
    ELABCTL_CONF_FILE="${HOME}/.config/elabctl.conf"
fi

# if elabctl is in current dir it has top priority
if [ -f elabctl.conf ]; then
    source elabctl.conf
    ELABCTL_CONF_FILE="elabctl.conf"
fi

# check that the path for the data dir is absolute
if [ "${DATA_DIR:0:1}" != "/" ]; then
    echo "Error in config file: DATA_DIR is not an absolute path!"
    echo "Edit elabctl.conf and add a full path to the directory."
    exit 1
fi

# check that the path for the upload dir is absolute
if [ "${UPLOAD_DIR:0:1}" != "/" ]; then
    echo "Error in config file: UPLOAD_DIR is not an absolute path!"
    echo "Edit elabctl.conf and add a full path to the directory."
    exit 1
fi

# available commands
declare -A commands
for valid in access-logs backup borg-backup bugreport error-logs help info infos initialize install logs mysql mysql-backup self-update start status stop refresh restart uninstall update update-db-schema upgrade usage version
do
    commands[$valid]=1
done

if [[ ${commands[$1]} ]]; then
    # exit if variable isn't set
    set -u
    ascii
    echo "Info: using elabctl configuration file: $ELABCTL_CONF_FILE"
    echo "Info: using elabftw configuration file: $CONF_FILE"
    echo "---------------------------------------------"
    $1
else
    help
    exit 1
fi
