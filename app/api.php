<?php
namespace Elabftw\Elabftw;

use Exception;

require_once '../config.php';
require_once ELAB_ROOT . 'vendor/autoload.php';

try {
    $Api = new Api($_REQUEST['req']);
    $Api->method = $_SERVER['REQUEST_METHOD'];

    if ($Api->method === 'GET') {
        echo $Api->getEntity();
    }

    if ($Api->method === 'POST') {
        echo $Api->updateEntity();
    }

} catch (Exception $e) {
    echo json_encode(array('error', $e->getMessage()));
}
