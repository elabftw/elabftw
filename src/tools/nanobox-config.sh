getEnv() {
	db_host=${DATA_MYSQL_HOST:-localhost}
	db_name=${APP_NAME:-elabftw}
	db_user=${DB_USER:-elabftw}
	db_password=${DATA_MYSQL_ROOT_PASS}
	server_name=${SERVER_NAME:-localhost}
	secret_key=${SECRET_KEY}
    max_php_memory=${MAX_PHP_MEMORY:-256M}
    max_upload_size=${MAX_UPLOAD_SIZE:-100M}
    php_timezone=${PHP_TIMEZONE:-Europe/Paris}
    php_max_children=${PHP_MAX_CHILDREN:-50}
}

writeConfigFile() {
	# write config file from env var
    config_path="/app/config.php"
	config="<?php
	define('DB_HOST', '${db_host}');
	define('DB_NAME', 'elabftw');
	define('DB_USER', 'root');
	define('DB_PASSWORD', '${db_password}');
	define('ELAB_ROOT', '/app/');
	define('SECRET_KEY', '${secret_key}');"
	echo "$config" > "$config_path"
    #chown nginx:nginx "$config_path"
    #chmod 600 "$config_path"
}
getEnv
writeConfigFile
