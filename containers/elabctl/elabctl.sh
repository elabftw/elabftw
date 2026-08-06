#!/usr/bin/env bash
# https://www.elabftw.net
# https://github.com/elabftw/elabftw
# © 2022 Nicolas CARPi @ Deltablot
# License: GPLv3
declare -r ELABCTL_VERSION='6.0.0'

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
declare -a DC=(docker compose)

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

# ask a yes/no question using Bash built-ins
function confirm
{
    local prompt=$1
    local default=${2:-no}
    local reply
    local suffix='[y/N]'

    if [ "$default" = "yes" ]; then
        suffix='[Y/n]'
    fi

    while true; do
        if ! read -r -p "$prompt $suffix " reply; then
            echo ""
            return 1
        fi

        if [ -z "$reply" ]; then
            if [ "$default" = "yes" ]; then
                return 0
            fi
            return 1
        fi

        case "$reply" in
            [Yy]|[Yy][Ee][Ss])
                return 0
                ;;
            [Nn]|[Nn][Oo])
                return 1
                ;;
            *)
                echo "Please answer yes or no."
                ;;
        esac
    done
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

    for bin in docker curl
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
    curl -Ls https://github.com/elabftw/elabftw/raw/master/containers/elabctl/elabctl.conf -o elabctl.conf
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
    if [ "$(ls -A "$DATA_DIR" 2>/dev/null)" ]; then
        echo "It looks like eLabFTW is already installed. Delete the ${DATA_DIR} folder to reinstall."
        exit 1
    fi

    declare servername='localhost'
    declare hasdomain=0
    declare usehttps=1
    declare useselfsigned=0

    # exit on error
    set -e

    cat <<EOF_INSTALL

Welcome to the installation of eLabFTW.

This script will install eLabFTW in Docker containers.

The main configuration file will be created at:
  ${CONF_FILE}

The MySQL data directory will be created at:
  ${DATA_DIR}/mysql

The uploaded files directory will be created at:
  ${UPLOAD_DIR}

Backups will be created at:
  ${BACKUP_DIR}

To change these settings, download and edit elabctl.conf before installing.
EOF_INSTALL

    if ! confirm "Continue with these settings?" yes; then
        get-user-conf
        exit 0
    fi

    # create the data dir
    mkdir -pv "$DATA_DIR"

    declare TMP_DIR
    TMP_DIR=$(mktemp -d)
    trap "rm -rf -- $(printf '%q' "$TMP_DIR")" EXIT
    declare TMP_CONF_FILE="${TMP_DIR}/elabftw.yml"

    ########################################################################
    # Ask for the domain name and whether HTTPS should be handled by the   #
    # eLabFTW container.                                                   #
    ########################################################################

    if confirm "Are you installing eLabFTW on a server? Answer no for a personal computer." yes; then
        if confirm "Does a domain name point to this server?" yes; then
            hasdomain=1
            servername=''
            while [ -z "$servername" ]; do
                if ! read -r -p "Enter the domain name (for example, elabftw.example.org): " servername; then
                    echo ""
                    exit 1
                fi
                if [ -z "$servername" ]; then
                    echo "A domain name is required."
                    continue
                fi
                if ! [[ $servername =~ ^[A-Za-z0-9]([A-Za-z0-9.-]*[A-Za-z0-9])?$ ]]; then
                    echo "Enter a hostname only, without a scheme or a path."
                    servername=''
                fi
            done
        else
            echo "Installation on a server without a proper domain name is not supported."
            exit 1
        fi

        echo ""
        echo "Use HTTPS unless another web server or load balancer already terminates TLS"
        echo "for this installation, such as Apache, Nginx, or HAProxy."
        if confirm "Should the eLabFTW container handle HTTPS?" yes; then
            usehttps=1

            echo ""
            echo "A proper certificate can come from Let's Encrypt or be provided by you."
            echo "A self-signed certificate is generated automatically, but browsers display a warning."
            if confirm "Use a proper TLS certificate? Answer no to let the container generate a self-signed certificate." yes; then
                useselfsigned=0
                echo "Configure the TLS certificate before starting the containers."
            else
                useselfsigned=1
                echo "A self-signed certificate will be generated when the container starts."
            fi
        else
            usehttps=0
        fi
    else
        servername="localhost"
        hasdomain=0
    fi

    echo ""
    echo "[1/4] Creating the folder structure."
    mkdir -pv "${DATA_DIR}/mysql" "${UPLOAD_DIR}"
    chmod -Rv 700 "${DATA_DIR}" "${UPLOAD_DIR}"
    chown -v 999:999 "${DATA_DIR}/mysql"
    chown -v 101:101 "${UPLOAD_DIR}"

    echo "[2/4] Preparing the Docker Compose configuration file."
    # make a copy of an existing conf file
    if [ -e "$CONF_FILE" ]; then
        echo "Making a copy of the existing configuration file."
        cp -v "$CONF_FILE" "${CONF_FILE}.old"
    fi

    # get a config file already filled with random passwords/keys
    echo "[3/4] Downloading the Docker Compose configuration file."
    curl --fail --show-error --silent --location \
        --connect-timeout 12 --max-time 42 \
        "https://get.elabftw.net/?config" -o "$TMP_CONF_FILE"
    if [ ! -s "$TMP_CONF_FILE" ]; then
        echo "Error: configuration download was empty." >&2
        exit 1
    fi

    # elab config
    echo "[4/4] Adjusting the configuration."
    sed -i -e "s/SERVER_NAME=localhost/SERVER_NAME=$servername/" "$TMP_CONF_FILE"
    sed -i -e "s:/var/elabftw/web:${UPLOAD_DIR}:" "$TMP_CONF_FILE"
    sed -i -e "s/container_name: elabftw/container_name: ${ELAB_WEB_CONTAINER_NAME}/" "$TMP_CONF_FILE"
    sed -i -e "s/container_name: mysql/container_name: ${ELAB_MYSQL_CONTAINER_NAME}/" "$TMP_CONF_FILE"

    # disable https
    scheme="https://"
    if [ "$usehttps" -eq 0 ]; then
        sed -i -e "s/DISABLE_HTTPS=false/DISABLE_HTTPS=true/" "$TMP_CONF_FILE"
        scheme="http://"
    fi

    # enable letsencrypt
    if [ "$hasdomain" -eq 1 ] && [ "$useselfsigned" -eq 0 ]; then
        # even if we don't use Let's Encrypt, for using TLS certs we need this to be true, and volume mounted
        sed -i -e "s:ENABLE_LETSENCRYPT=false:ENABLE_LETSENCRYPT=true:" "$TMP_CONF_FILE"
        sed -i -e "s:#- /etc/letsencrypt:- /etc/letsencrypt:" "$TMP_CONF_FILE"
    fi

    sed -i -e "s#SITE_URL=#SITE_URL=$scheme$servername#" "$TMP_CONF_FILE"

    # setup restrictive permissions
    chmod 600 "$TMP_CONF_FILE"

    # now move conf file to the configured location
    mv "$TMP_CONF_FILE" "$CONF_FILE"

    rmdir -v "$TMP_DIR"

    cat <<EOF_INSTALL

Installation finished successfully.

Next steps:
  1. Configure TLS certificates when applicable.
  2. Start the containers:
       elabctl start
  3. Import the database structure:
       elabctl initialize
  4. Open:
       ${scheme}${servername}

Post-installation documentation:
  https://doc.elabftw.net/sysadmin-guide.html#setting-up-email

Docker Compose configuration:
  ${CONF_FILE}

Data directory:
  ${DATA_DIR}

Follow the web container logs with:
  docker logs -f ${ELAB_WEB_CONTAINER_NAME}
EOF_INSTALL

    if confirm 'Start the containers now? This will run the "start" command.'; then
        start
        if confirm 'Run the database initialization? This will run the "initialize" command.'; then
            echo -n "Waiting a few seconds for ${ELAB_WEB_CONTAINER_NAME} to start..."
            sleep 1
            echo -n "."
            sleep 1
            echo -n "."
            sleep 1
            echo -n "."
            sleep 1
            echo "."
            initialize
        fi
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
    fi

    set -e

    # get clean date
    local -r date=$(date +%Y-%m-%d_%H-%M-%S) # 2016-02-10_20-12-45
    local -r dumpfile="${BACKUP_DIR}/mysql_dump-${date}.sql"

    # dump sql
    # only consider the exit code of mysqldump for the next step (docker cp) and not the grep
    if docker exec "${ELAB_MYSQL_CONTAINER_NAME}" bash -c '
        mysqldump \
          -u"$MYSQL_USER" \
          -p"$MYSQL_PASSWORD" \
          -r dump.sql \
          --no-tablespaces \
          "$MYSQL_DATABASE" 2>&1 |
          grep -vF "[Warning] Using a password"

      statuses=("${PIPESTATUS[@]}")
      (( statuses[0] == 0 && statuses[1] <= 1 ))
      '; then
      # copy it from the container to the host
      docker cp "${ELAB_MYSQL_CONTAINER_NAME}:dump.sql" "$dumpfile" && docker exec "${ELAB_MYSQL_CONTAINER_NAME}" rm dump.sql
    else
      echo ">> Containers must be running to do the backup!" >&2
    fi
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
    curl -sL https://raw.githubusercontent.com/elabftw/elabftw/master/containers/elabctl/elabctl.sh -o "$tmp_filepath"
    chmod -v +x "$tmp_filepath"
    mv -v "$tmp_filepath" "$me"
    rmdir -v "$TMP_DIR"
}

function start
{
    is-installed
    "${DC[@]}" -f "$CONF_FILE" up -d
}

function status
{
    is-installed
    "${DC[@]}" -f "$CONF_FILE" ps
}

function stop
{
    is-installed
    "${DC[@]}" -f "$CONF_FILE" down
}

function uninstall
{
    echo ""
    echo "WARNING: This will delete everything related to eLabFTW on this computer."
    echo "There is no undo operation."
    if ! confirm "Continue with the uninstall?"; then
        echo "Uninstall cancelled."
        exit 1
    fi

    stop

    local rmbackup='n'
    if confirm "Delete the backups too?"; then
        rmbackup='y'
    fi

    echo ""
    echo "Removal will begin in 10 seconds."
    local countdown_response=''
    if read -r -t 10 -p "Press Enter to continue immediately, or type 'cancel' to abort: " countdown_response; then
        case "$countdown_response" in
            [Cc]|[Cc][Aa][Nn][Cc][Ee][Ll])
                echo "Uninstall cancelled."
                exit 1
                ;;
        esac
    fi
    echo ""

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
        rm -rvf "$UPLOAD_DIR"
        echo "[x] Deleted $UPLOAD_DIR"
    fi
    # remove data directory
    if [ -d "$DATA_DIR" ]; then
        rm -rvf "$DATA_DIR"
        echo "[x] Deleted $DATA_DIR"
    fi
    # remove backup dir
    if [ "$rmbackup" = 'y' ] && [ -d "$BACKUP_DIR" ]; then
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
    "${DC[@]}" -f "$CONF_FILE" pull
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

# all operational commands manage system-wide files and the rootful Docker daemon
if (( EUID != 0 )); then
    echo "Error: elabctl must be run as root." >&2
    echo "Run it as root, for example with sudo or doas." >&2
    exit 1
fi

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

UPLOAD_DIR="${UPLOAD_DIR:-${DATA_DIR}/web}"

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
