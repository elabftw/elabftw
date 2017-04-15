<?php
/**
 * app/controllers/ApiController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

/**
 * This file is called without any auth, so we don't load init.inc.php but only what we need
 */
require_once '../../config.php';
require_once ELAB_ROOT . 'vendor/autoload.php';

try {
    // do we have an API key?
    if (empty($_SERVER['HTTP_AUTHORIZATION'])) {
        throw new Exception('No API key received.');
    }
    $Api = new Api(
        $_SERVER['HTTP_AUTHORIZATION'],
        $_SERVER['REQUEST_METHOD'],
        $_REQUEST['req']
    );

    if ($Api->method === 'GET') {
        $output = $Api->getEntity();
    } else {

        // file upload
        if (count($_FILES) >= 1) {
            $output = $Api->uploadFile();
        // title date body update
        } else {
            $output = $Api->updateEntity();
        }
    }

    echo json_encode($output);

} catch (Exception $e) {
    echo json_encode(array('error', $e->getMessage()));
}
