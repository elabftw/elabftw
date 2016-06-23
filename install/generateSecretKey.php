<?php
use Defuse\Crypto\Key as Key;

// generate a secret key
$root = str_replace('install', '', dirname(__FILE__));
require_once $root . 'vendor/autoload.php';
$new_key = Key::createNewRandomKey();
echo $new_key->saveToAsciiSafeString();
