<?php
/**
 * app/ove.php
 *
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Models\Database;
use Elabftw\Models\Uploads;
use Symfony\Component\HttpFoundation\Response;

/**
 *  Plasmid Editor
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Plasmid Editor');

$Response = new Response();
$Response->prepare($Request);

try {
    if ($Request->query->has('f')) {
        $uploadID = (int) $Request->query->get('f');
    }

    $Entity = new Database($App->Users);
    // $upload = $Entity->Uploads->readFromId($uploadID);

    $template = 'ove.html';
    $renderArr = array(
        'uploadsArr' => $Entity->Uploads->readFromId($uploadID),
        'hideTitle' => true,
    );

    $Response->setContent($App->render($template, $renderArr));
} catch (ImproperActionException | Error $e) {
    $Response->setContent($e->getMessage());
} finally {
    $Response->send();
}
