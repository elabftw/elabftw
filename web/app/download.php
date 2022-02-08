<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Aws\Credentials\Credentials;
use const ELAB_AWS_ACCESS_KEY;
use const ELAB_AWS_SECRET_KEY;
use Elabftw\Controllers\DownloadController;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Services\LocalAdapter;
use Elabftw\Services\S3Adapter;
use function error_reporting;
use Exception;
use League\Flysystem\Filesystem;
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
    $longName = (string) $Request->query->get('f');
    if (strpos($longName, "\0") !== false) {
        throw new IllegalActionException('Missing parameter for download');
    }

    if ($Request->query->get('storage') === '2') {
        $adapter = new S3Adapter($App->Config, new Credentials(ELAB_AWS_ACCESS_KEY, ELAB_AWS_SECRET_KEY));
    } else {
        $adapter = new LocalAdapter();
    }
    $fs = new Filesystem($adapter->getAdapter());

    $DownloadController = new DownloadController(
        $fs,
        $longName,
        (string) $Request->query->get('name'),
        $Request->query->has('forceDownload'),
    );
    $Response = $DownloadController->getResponse();
    $Response->prepare($App->Request);
    $Response->send();
} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Download error', $e)));
    $App->Session->getFlashBag()->add('ko', Tools::error());
}
