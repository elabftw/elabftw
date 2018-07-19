getEnv() {
	db_host=${DATA_MYSQL_HOST:-localhost}
	db_password=${DATA_MYSQL_ROOT_PASS}
}

writeConfigFile() {
	# write config file from env var
    config_path="/app/config.php"
    secret_key=$(curl -L https://demo.elabftw.net/install/generateSecretKey.php)
	config="<?php
	define('DB_HOST', '${db_host}');
	define('DB_NAME', 'elabftw');
	define('DB_USER', 'root');
	define('DB_PASSWORD', '${db_password}');
    define('SECRET_KEY', '${secret_key}');"
	echo "$config" > "$config_path"
}
getEnv
writeConfigFile
