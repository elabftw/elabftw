<?php
/**
 * app/logout.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
session_start();
session_destroy();
setcookie('token', '', time() - 3600, '/', null, true, true);
header('Location: ../login.php');
