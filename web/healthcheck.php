<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Symfony\Component\HttpFoundation\Response;

/**
 * Make sure everything is fine and dandy as far as we can tell
 * This page exists because other monitoring endpoints are not complete (they do not include the db)
 * And because hitting login.php or similar is wasteful, and also this allows to filter it out in the access logs if needed
 */
require_once dirname(__DIR__) . '/vendor/autoload.php';

$out = 'ko';
$status = 500;

try {
    $Db = Db::getConnection();
    $req = $Db->prepare('SELECT 12');
    if ($req->execute()) {
        $out = 'ok';
        $status = 200;
    }
} finally {
    $Response = new Response();
    $Response->setContent($out);
    $Response->setStatusCode($status);
    $Response->send();
}
