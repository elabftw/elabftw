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
use Elabftw\Enums\Storage;
use Elabftw\Exceptions\AppException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use Exception;
use Symfony\Component\HttpFoundation\Response;

use function error_reporting;
use function header;
use function headers_sent;
use function http_response_code;
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
        throw new ImproperActionException('Missing parameter for download');
    }

    $storage = $App->Request->query->getInt('storage');
    // backward compatibility: the download links in body won't have the storage param
    if ($storage === 0) {
        // we fallback on the instance's configured storage
        $storage = (int) Config::getConfig()->configArr['uploads_storage'];
    }
    $storageEnum = Storage::tryFrom($storage);
    // Only uploads are served here: other storages (exports, cache…) have no uploads row to authorize against.
    if ($storageEnum !== Storage::LOCAL && $storageEnum !== Storage::S3) {
        throw new ImproperActionException('Invalid storage for download');
    }
    $storageFs = $storageEnum->getStorage()->getFs();

    $DownloadController = new DownloadController(
        $storageFs,
        $longName,
        $App->Users,
        $storageEnum,
        $App->Request->query->getString('name'),
        $App->Request->query->has('forceDownload'),
    );
    $Response = $DownloadController->getResponse($App->Request);
    $Response->prepare($App->Request);
    $Response->send();
} catch (Exception $e) {
    // an empty 200 would look like a successful download of an empty file
    $code = $e instanceof AppException ? $e->getHttpCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
    // a streamed response may already have sent its headers: nothing can be changed then
    if (!headers_sent()) {
        http_response_code($code);
        header('Cache-Control: private, no-store');
    }
    if ($e instanceof AppException) {
        // client errors are logged as notices, server errors as errors
        $e->emitLog($App->Log, (int) ($App->Session->get('userid') ?? -1));
    } else {
        $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Download error', $e)));
    }
}
