<?php
// generate a secret key
$root = str_replace('install', '', dirname(__FILE__));
require_once $root . 'vendor/defuse/php-encryption/autoload.php';
$crypto = new \Defuse\Crypto\Crypto();
echo bin2hex($crypto::createNewRandomKey());
