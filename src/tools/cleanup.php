<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Services;

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;

/**
 * This is used to find out if there are untracked files that should have been deleted
 * but were not deleted because of a bug fixed in 2.0.7
 */
require_once \dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once \dirname(__DIR__, 2) . '/config.php';

try {
    $UploadsCleaner = new UploadsCleaner();
    $orphans = $UploadsCleaner->cleanup();
    printf("Deleted %d files\n", $orphans);
} catch (FilesystemErrorException | DatabaseErrorException $e) {
    echo $e->getMessage();
}
