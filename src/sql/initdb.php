<?php
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Symfony\Component\HttpFoundation\Request;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

// CREATE REQUEST OBJECT
$Request = Request::createFromGlobals();

$configFilePath = dirname(__DIR__, 2) . '/config.php';
require_once $configFilePath;
$Config = new Config();
$Teams = new Teams(new Users());
$Teams->create('Default team');
$Users = new Users(null, new Auth($Request), $Config);
$Users->create('osef@yopmail.com', 1, 'Alice', 'Lastname', '123435678auie');
