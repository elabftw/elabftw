#!/bin/bash
#
# @author Nicolas CARPi <nico-git@deltablot.email>
# @copyright 2020 Nicolas CARPi
# @see https://www.elabftw.net Official website
# @license AGPL-3.0
# @package elabftw/elabimg
#
# This script is called by the oneshot service "init"
# It will get config from env and adjust configuration files and system accordingly

# get env values
# and unset the sensitive ones so they cannot be accessed by a rogue process
getEnv() {
    custom_connect_src=${CUSTOM_CONNECT_SRC:-}
    db_host=${DB_HOST:-localhost}
    db_port=${DB_PORT:-3306}
    db_name=${DB_NAME:-elabftw}
    db_user=${DB_USER:-elabftw}
    # Note: no default value here
    db_password=${DB_PASSWORD:-}
    db_cert_path=${DB_CERT_PATH:-}
    site_url=${SITE_URL:-https://localhost}
    # remove trailing slash for site_url
    site_url=$(echo "${site_url}" | sed 's:/$::')
    server_name=${SERVER_NAME:-localhost}
    disable_https=${DISABLE_HTTPS:-false}
    enable_letsencrypt=${ENABLE_LETSENCRYPT:-false}
    secret_key=${SECRET_KEY:-}
    max_php_memory=${MAX_PHP_MEMORY:-2G}
    max_upload_size=${MAX_UPLOAD_SIZE:-100M}
    max_upload_time=${MAX_UPLOAD_TIME:-900000}
    # CIS benchmark nginx 2.0.0 2.4.3
    keepalive_timeout=${KEEPALIVE_TIMEOUT:-10s}
    php_timezone=${PHP_TIMEZONE:-Europe/Paris}
    set_real_ip=${SET_REAL_IP:-false}
    set_real_ip_from=${SET_REAL_IP_FROM:-192.168.31.48}
    php_max_children=${PHP_MAX_CHILDREN:-50}
    php_max_execution_time=${PHP_MAX_EXECUTION_TIME:-120}
    use_redis=${USE_REDIS:-false}
    redis_host=${REDIS_HOST:-redis}
    redis_port=${REDIS_PORT:-6379}
    redis_username=${REDIS_USERNAME:-}
    redis_password=${REDIS_PASSWORD:-}
    # value for nginx's worker_processes setting
    nginx_work_proc=${NGINX_WORK_PROC:-auto}
    dev_mode=${DEV_MODE:-false}
    demo_mode=${DEMO_MODE:-false}
    maintenance_mode=${MAINTENANCE_MODE:-false}
    auto_db_init=${AUTO_DB_INIT:-false}
    auto_db_update=${AUTO_DB_UPDATE:-false}
    aws_ak=${ELAB_AWS_ACCESS_KEY:-}
    aws_sk=${ELAB_AWS_SECRET_KEY:-}
    ldap_tls_reqcert=${LDAP_TLS_REQCERT:-false}
    allow_origin=${ALLOW_ORIGIN:-}
    allow_methods=${ALLOW_METHODS:-}
    allow_headers=${ALLOW_HEADERS:-}
    status_password=${STATUS_PASSWORD:-}
    use_indigo=${USE_INDIGO:-false}
    indigo_url=${INDIGO_URL:-https://chem-plugin.elabftw.net/}
    use_fingerprinter=${USE_FINGERPRINTER:-false}
    fingerprinter_use_proxy=${FINGERPRINTER_USE_PROXY:-false}
    fingerprinter_url=${FINGERPRINTER_URL:-https://example.com:8000/}
    use_opencloning=${USE_OPENCLONING:-false}
    opencloning_url=${OPENCLONING_URL:-https://opencloning.elabftw.net/}
    use_persistent_mysql_conn=${USE_PERSISTENT_MYSQL_CONN:-true}
    pubchem_pug_url=${PUBCHEM_PUG_URL:-https://pubchem.ncbi.nlm.nih.gov/rest/pug}
    pubchem_pug_view_url=${PUBCHEM_PUG_VIEW_URL:-https://pubchem.ncbi.nlm.nih.gov/rest/pug_view/data}
}

checkSiteUrl() {
    if [ "${site_url}" == "https://localhost" ]; then
        echo "docker-entrypoint.sh: notice: SITE_URL variable is set to default value. Make sure to configure this environment variable correctly." >&2
    fi
}

copyConf() {
    mkdir -pv /run/php
    f="/run/php/elabpool.conf"
    cp -v /etc/php84/php-fpm.d/elabpool.conf.tpl $f
    chmod -v 600 $f
    mkdir -pv /run/nginx
    n="/run/nginx/nginx.conf"
    cp -v /etc/nginx/nginx.conf.tpl $n
    chmod -v 600 $n
}

# fullchain.pem and privkey.pem should be in a volume linked to /ssl
generateCert() {
    mkdir -pv /run/elabftw/nginx/certs
    if [ ! -f /run/elabftw/nginx/certs/server.crt ]; then

        # here we generate a random CN because of this bug:
        # https://bugzilla.redhat.com/show_bug.cgi?id=1204670
        # https://bugzilla.mozilla.org/show_bug.cgi?id=1056341
        # this way there is no more hangs
        randcn=$(openssl rand -hex 6)
        openssl req \
            -new \
            -newkey rsa:4096 \
            -days 9999 \
            -nodes \
            -x509 \
            -subj "/C=FR/ST=France/L=Paris/O=elabftw/CN=$randcn" \
            -keyout /run/elabftw/nginx/certs/server.key \
            -out /run/elabftw/nginx/certs/server.crt
    fi
}

escape_sed_repl() {
    printf '%s' "$1" | sed -e 's/[\/&]/\\&/g'
}

nginxConf() {
    mkdir -pv /run/nginx/conf.d
    cp -v /etc/nginx/common.conf.tpl /run/nginx/common.conf
    conf="/run/nginx/conf.d"
    server_conf="${conf}/server.conf"
    # Switch http or https
    # false by default
    if ($disable_https); then
        # activate an HTTP server listening on port 443
        cp -v /etc/nginx/http.conf $server_conf

    # HTTPS
    else
        mkdir -p /etc/nginx/certs
        # generate a selfsigned certificate if we don't use Let's Encrypt
        if (! $enable_letsencrypt); then
            generateCert
        fi
        # activate an HTTPS server listening on port 443
        cp -v /etc/nginx/https.conf /run/nginx/conf.d/server.conf
        if ($enable_letsencrypt); then
            mkdir -p /ssl
            sed -i -e "s:%CERT_PATH%:/ssl/live/${server_name}/fullchain.pem:" $server_conf
            sed -i -e "s:%KEY_PATH%:/ssl/live/${server_name}/privkey.pem:" $server_conf
        else
            sed -i -e "s:%CERT_PATH%:/run/elabftw/nginx/certs/server.crt:" $server_conf
            sed -i -e "s:%KEY_PATH%:/run/elabftw/nginx/certs/server.key:" $server_conf
        fi
    fi
    # set the server name in nginx config
    # works also for the ssl config if ssl is enabled
    sed -i -e "s/%SERVER_NAME%/${server_name}/" $server_conf

    # for maintenance mode we replace common.conf with maintenance.conf
    if ($maintenance_mode); then
        ln -fs /etc/nginx/maintenance.conf /run/nginx/common.conf
    fi

    # set the list of php files that can be processed by php-fpm
    php_files_nginx_allowlist=$(find /elabftw/web -type f -name '*.php' | sed 's:/elabftw/web/::' | tr '\n' '|' | sed 's/|$//')
    # use : because of the / in the list of files
    sed -i -e "s:%PHP_FILES_NGINX_ALLOWLIST%:${php_files_nginx_allowlist}:" /run/nginx/common.conf

    # adjust keepalive_timeout
    sed -i -e "s/%KEEPALIVE_TIMEOUT%/${keepalive_timeout}/" /run/nginx/nginx.conf

    # adjust client_max_body_size
    sed -i -e "s/%CLIENT_MAX_BODY_SIZE%/${max_upload_size}/" /run/nginx/nginx.conf

    # ADJUST PLUGINS
    if [ "$indigo_url" != "false" ] && [ -n "$indigo_url" ] && [ "$use_indigo" != "false" ] && [ -n "$use_indigo" ]; then
        cp -v /etc/nginx/indigo.conf.tpl /run/nginx/indigo.conf
        sed -i -e "s|^#\s*include /etc/nginx/indigo.conf|include /etc/nginx/indigo.conf|" /run/nginx/common.conf
        sed -i -e "s|%INDIGO_URL%|${indigo_url}|" /run/nginx/indigo.conf
    fi
    if [ "$opencloning_url" != "false" ] && [ -n "$opencloning_url" ] && [ "$use_opencloning" != "false" ] && [ -n "$use_opencloning" ]; then
        cp -v /etc/nginx/opencloning.conf.tpl /run/nginx/opencloning.conf
        # remove the trailing / if it exists, or it doesn't work
        oc_url=${opencloning_url%/}
        sed -i -e "s|^#\s*include /etc/nginx/opencloning.conf|include /etc/nginx/opencloning.conf|" /run/nginx/common.conf
        sed -i -e "s|%OPENCLONING_URL%|${oc_url}|" /run/nginx/opencloning.conf
    fi

    # SET REAL IP CONFIG
    if ($set_real_ip); then
        # read the IP addresses from env
        IFS=', ' read -r -a ip_arr <<< "${set_real_ip_from}"
        conf_string=""
        for element in "${ip_arr[@]}"
        do
            conf_string+="set_real_ip_from ${element};"
        done
        # use pipe for sed separation because CIDR might have a /
        sed -i -e "s|#%REAL_IP_CONF%|${conf_string}|" /run/nginx/common.conf
        # enable real_ip_header config
        sed -i -e "s/#real_ip_header X-Forwarded-For;/real_ip_header X-Forwarded-For;/" /run/nginx/common.conf
        sed -i -e "s/#real_ip_recursive on;/real_ip_recursive on;/" /run/nginx/common.conf
    fi

    # SET WORKER PROCESSES (default is auto)
    sed -i -e "s/%WORKER_PROCESSES%/${nginx_work_proc}/" /run/nginx/nginx.conf

    # no unsafe-eval in prod
    unsafe_eval=""
    # DEV MODE
    # we don't want to serve brotli/gzip compressed assets in dev (or we would need to recompress them after every change!)
    if ($dev_mode); then
        rm -f /etc/nginx/conf.d/brotli.conf /etc/nginx/conf.d/gzip.conf
        # to allow webpack in watch/dev mode we need to allow unsafe-eval for script-src
        unsafe_eval="'unsafe-eval'"
        # fix git complaining: fatal: detected dubious ownership in repository at '/elabftw'
        git config --global --add safe.directory /elabftw
    fi
    # set unsafe-eval in CSP
    sed -i -e "s/%UNSAFE-EVAL4DEV%/${unsafe_eval}/" /run/nginx/common.conf
    sed -i -e "s/%CUSTOM_CONNECT_SRC%/$(escape_sed_repl "${custom_connect_src}")/" /run/nginx/common.conf
    # put a random short string as the server header to prevent fingerprinting
    server_header=$(openssl rand -hex 2 | cut -c1-3)
    sed -i -e "s/%SERVER_HEADER%/${server_header}/" /run/nginx/common.conf
    # add Access-Control-Allow-Origin header if enabled
    acao_header=""
    if [ -n "$allow_origin" ]; then
        acao_header="more_set_headers 'Access-Control-Allow-Origin: ${allow_origin}';"
    fi
    sed -i -e "s#%ACAO_HEADER%#${acao_header}#" /run/nginx/common.conf
    # add Access-Control-Allow-Methods header if enabled
    acam_header=""
    if [ -n "$allow_methods" ]; then
        acam_header="more_set_headers 'Access-Control-Allow-Methods: ${allow_methods}';"
    fi
    sed -i -e "s/%ACAM_HEADER%/${acam_header}/" /run/nginx/common.conf
    # add Access-Control-Allow-Headers header if enabled
    acah_header=""
    if [ -n "$allow_headers" ]; then
        acah_header="more_set_headers 'Access-Control-Allow-Headers: ${allow_headers}';"
    fi
    sed -i -e "s/%ACAH_HEADER%/${acah_header}/" /run/nginx/common.conf

    # create a password file for /php-status endpoint
    if [ -z "$status_password" ]; then
        # if no password is provided, instead of hardcoding a default password, we generate one
        status_password=$(openssl rand -hex 16)
    fi
    # instead of installing htpasswd, use openssl that is already here
    printf "elabftw:%s\n" "$(openssl passwd -apr1 "$status_password")" > /run/nginx/passwords
    chmod 400 /run/nginx/passwords
}

# PHP-FPM CONFIG
phpfpmConf() {
    f="/run/php/elabpool.conf"
    # create a PSK for invoker
    INVOKER_PSK=$(openssl rand -base64 42)
    # allow php to read it. use | separator as / is in base64
    sed -i -e "s|^env\[INVOKER_PSK\] = .*|env[INVOKER_PSK] = ${INVOKER_PSK}|" $f
    # increase max number of simultaneous requests
    sed -i -e "s/%PHP_MAX_CHILDREN%/${php_max_children}/" $f
    # allow using more memory for php-fpm
    sed -i -e "s/%PHP_MAX_MEMORY%/${max_php_memory}/" $f
    # external services, we want to easily know from php app if they are available
    sed -i -e "s/%USE_INDIGO%/${use_indigo}/" $f
    sed -i -e "s/%USE_FINGERPRINTER%/${use_fingerprinter}/" $f
    sed -i -e "s/%FINGERPRINTER_USE_PROXY%/${fingerprinter_use_proxy}/" $f
    # use # because url has / in it
    sed -i -e "s#%FINGERPRINTER_URL%#${fingerprinter_url}#" $f
    sed -i -e "s/%USE_OPENCLONING%/${use_opencloning}/" $f
    # persistent mysql connection setting
    sed -i -e "s/%USE_PERSISTENT_MYSQL_CONN%/${use_persistent_mysql_conn}/" $f
    sed -i -e "s/%DEV_MODE%/${dev_mode}/" $f
    sed -i -e "s/%DEMO_MODE%/${demo_mode}/" $f
    # pubchem urls
    sed -i -e "s|%PUBCHEM_PUG_URL%|${pubchem_pug_url}|" $f
    sed -i -e "s|%PUBCHEM_PUG_VIEW_URL%|${pubchem_pug_view_url}|" $f
}

getRedisUri() {
    username=""
    password=""
    # the & and ? are escaped because of the sed
    # it's probably a good idea to not have to many weird characters in redis username/password
    query_link="\&"
    if [ -n "$redis_username" ]; then
        username="\?auth[user]=${redis_username}"
    fi
    if [ -n "$redis_password" ]; then
        if [ -z "$redis_username" ]; then
            query_link="\?"
        fi
        password="${query_link}auth[pass]=${redis_password}"
    fi
    # add a set of quotes or the = sign will pose problem in php.ini
    printf "\"tcp://%s:%d%s%s\"" "$redis_host" "$redis_port" "$username" "$password"
}

# PHP CONFIG
phpConf() {
    src="/etc/php84/php.ini.tpl"
    f="/run/php/php.ini"
    cp -v $src $f
    # allow using more memory for php
    sed -i -e "s/%PHP_MEMORY_LIMIT%/${max_php_memory}/" $f
    # change upload_max_filesize and post_max_size
    sed -i -e "s/%PHP_MAX_UPLOAD_SIZE%/${max_upload_size}/" $f

    # PHP SESSIONS
    # default values for sessions (with files)
    sess_save_handler="files"
    sess_save_path="/run/elabftw/sessions"
    # if we use redis then sessions are handled differently
    if ($use_redis); then
        sess_save_handler="redis"
        sess_save_path=$(getRedisUri)
    else
        # create the custom session dir
        mkdir -p "$sess_save_path"
        chmod 700 "$sess_save_path"
    fi
    # now set the values
    sed -i -e "s:%SESSION_SAVE_HANDLER%:${sess_save_handler}:" $f
    sed -i -e "s|%SESSION_SAVE_PATH%|${sess_save_path}|" $f

    # config for timezone, use : because timezone will contain /
    sed -i -e "s:%TIMEZONE%:${php_timezone}:" $f
    # allow longer requests execution time
    sed -i -e "s/%PHP_MAX_EXECUTION_TIME%/${php_max_execution_time}/" $f

    # production open_basedir conf value
    # /etc/ssl/cert.pem is for openssl and timestamp related functions
    # for /run/s6-rc... see elabftw/elabftw#5249
    open_basedir="/.dockerenv:/elabftw/:/tmp/:/run/elabftw/cache:/usr/bin/unzip:/etc/ssl/cert.pem:/run/s6-rc/servicedirs/s6rc-oneshot-runner"
    # DEV MODE
    if ($dev_mode); then
        # we don't want to use opcache as we want our changes to be immediately visible
        sed -i -e "s/opcache\.enable=1/opcache\.enable=0/" $f
        # completely remove open_basedir in dev because it's a pita with phpstan for instance
        open_basedir=""
        # rector needs tmpfile, so allow it in dev mode
        sed -i -e "s/tmpfile, //" $f
    fi
    # now set value for open_basedir
    sed -i -e "s|%OPEN_BASEDIR%|${open_basedir}|" $f
}

elabftwConf() {
    mkdir -p /run/elabftw/uploads /run/elabftw/cache/elab /run/elabftw/cache/mpdf /run/elabftw/cache/twig /run/elabftw/cache/purifier/CSS /run/elabftw/cache/purifier/HTML /run/elabftw/cache/purifier/URI /run/elabftw/exports
    # necessary so php user can write to it in podman rootless
    chmod -R g+w /run/elabftw/cache
}

ldapConf() {
    mkdir -p /etc/openldap
    if [ "$ldap_tls_reqcert" != false ]; then
        # remove a possibly existing line or it will append every time container is restarted
        sed -i -e '/^TLS_REQCERT/d' /etc/openldap/ldap.conf
        echo "TLS_REQCERT ${ldap_tls_reqcert}" >> /etc/openldap/ldap.conf
    fi
}

populatePhpEnv() {

    f="/run/php/elabpool.conf"
    sed -i -e "s/%DB_HOST%/${db_host}/" $f
    sed -i -e "s/%DB_PORT%/${db_port}/" $f
    sed -i -e "s/%DB_NAME%/${db_name}/" $f
    sed -i -e "s/%DB_USER%/${db_user}/" $f
    sed -i -e "s/%DB_PASSWORD%/$(escape_sed_repl "${db_password}")/" $f
    # don't add empty stuff
    if [ -n "$db_cert_path" ]; then
        # use # as separator instead of slash
        sed -i -e "s#%DB_CERT_PATH%#${db_cert_path}#" $f
    else
        # remove this if not in use
        sed -i -e "/%DB_CERT_PATH%/d" $f
    fi
    sed -i -e "s/%SECRET_KEY%/${secret_key}/" $f
    sed -i -e "s/%MAX_UPLOAD_SIZE%/${max_upload_size}/" $f
    sed -i -e "s/%MAX_UPLOAD_TIME%/${max_upload_time}/" $f
    # use # as separator instead of slash
    sed -i -e "s#%SITE_URL%#${site_url}#" $f
    # assume that if ak is set, then sk is too
    if [ -n "$aws_ak" ]; then
        sed -i -e "s|%ELAB_AWS_ACCESS_KEY%|${aws_ak}|" $f
        sed -i -e "s|%ELAB_AWS_SECRET_KEY%|${aws_sk}|" $f
    else
        sed -i -e "/%ELAB_AWS_ACCESS_KEY%/d" $f
        sed -i -e "/%ELAB_AWS_SECRET_KEY%/d" $f
    fi
}

# display a friendly message with running versions
startupMessage() {
    nginx_version=$(/usr/sbin/nginx -v 2>&1)
    http_mode=$([ "$disable_https" = true ] && echo "HTTP" || echo "HTTPS")
    say "info: eLabFTW version: ${ELABFTW_VERSION}"
    say "info: ${nginx_version}"
    say "info: s6-overlay version: ${S6_OVERLAY_VERSION}"
    say "info: runtime configuration successfully finished"
    say "info: starting server listening internally on port 8080 in ${http_mode}"
}

# Automatically initialize the database structure
dbInit() {
    if ($auto_db_init); then
        say "info: initializing database structure"
        /elabftw/bin/init db:install
    fi
}

# Automatically update the database schema
dbUpdate() {
    if ($auto_db_update); then
        say "info: updating database structure"
        /elabftw/bin/console db:update
    fi
}

say() {
    echo "entrypoint: $1"
}

warnDeprecated() {
    local var_name

    for var_name in "$@"; do
        if [[ -v "$var_name" ]]; then
            say "Warning: environment variable $var_name is deprecated and will be ignored. Please remove it from your container configuration."
        fi
    done
}

# SCRIPT START
getEnv
checkSiteUrl
copyConf
nginxConf
phpfpmConf
phpConf
elabftwConf
ldapConf
populatePhpEnv
dbInit
dbUpdate
startupMessage
# add it at the end so it's maybe more visible
warnDeprecated \
    ENABLE_IPV6 \
    ELABFTW_USER \
    ELABFTW_GROUP \
    ELABFTW_USERID \
    ELABFTW_GROUPID \
    SILENT_INIT
