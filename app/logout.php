<?php
/**
 * app/logout.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';
// create Request object
$Request = Request::createFromGlobals();

$Session = new Session();

$Session->start();
$Session->invalidate();
setcookie('token', '', time() - 3600, '/', null, true, true);
header('Location: ../login.php');
