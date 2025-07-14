<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Controllers\DownloadController;
use Elabftw\Enums\Messages;
use Elabftw\Enums\Storage;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Models\Config;
use Exception;

use function error_reporting;
use function set_time_limit;
use function strpos;

require_once 'init.inc.php';

try {
    // we disable errors to avoid having notice and warning polluting our file
    error_reporting(E_ERROR);

    // Make sure program execution doesn't time out
    // Set maximum script execution time in seconds (0 means no limit)
    set_time_limit(0);

    // Check for LONG_NAME
    $longName = $App->Request->query->getString('f');
    if (strpos($longName, "\0") !== false) {
        throw new IllegalActionException('Missing parameter for download');
    }

    $storage = $App->Request->query->getInt('storage');
    // backward compatibility: the download links in body won't have the storage param
    if ($storage === 0) {
        // we fallback on the instance's configured storage
        $storage = (int) Config::getConfig()->configArr['uploads_storage'];
    }
    $storageFs = Storage::from($storage)->getStorage()->getFs();

    $DownloadController = new DownloadController(
        $storageFs,
        $longName,
        $App->Request->query->getString('name'),
        $App->Request->query->has('forceDownload'),
    );
    $Response = $DownloadController->getResponse();
    $Response->prepare($App->Request);
    $Response->send();
} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Download error', $e)));
    $App->Session->getFlashBag()->add('ko', Messages::GenericError->toHuman());
}
