<?php
/**
 * generateSecretKey.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

use Defuse\Crypto\Key as Key;

/**
 * Generate a secret key for the config file
 *
 */
require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';
echo Key::createNewRandomKey()->saveToAsciiSafeString();
