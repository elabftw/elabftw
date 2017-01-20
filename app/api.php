<?php
namespace Elabftw\Elabftw;

use Exception;

require_once '../config.php';
require_once ELAB_ROOT . 'vendor/autoload.php';

try {
    $Api = new Api($_SERVER['REQUEST_METHOD'], $_REQUEST['req']);

    if ($Api->method === 'GET') {
        $output = $Api->getEntity();
    } else {

        if (count($_FILES) >= 1) {
            $output = $Api->uploadFile();
        } else {
            $output = $Api->updateEntity();
        }
    }

    echo json_encode($output);

} catch (Exception $e) {
    echo json_encode(array('error', $e->getMessage()));
}
