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

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Database;
use Elabftw\Models\Uploads;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 *  Plasmid Editor
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Plasmid Viewer');

$Response = new Response();
$Response->prepare($Request);
$template = 'error.html';

try {
    $uploadLongName = '';
    if ($Request->query->has('f')) {
        $uploadLongName = (string) $Request->query->get('f');
    }

    $Entity = new Database($App->Users);
    // $upload = $Entity->Uploads->readFromId($uploadID);

    $template = 'ove.html';
    $renderArr = array(
        'upload_long_name' => $uploadLongName,
        'hideTitle' => true,
    );

    $Response->setContent($App->render($template, $renderArr));
} catch (ImproperActionException $e) {
    // show message to user
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (Exception $e) {
    // log error and show general error message
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $renderArr = array('error' => Tools::error());
    $Response->setContent($App->render($template, $renderArr));
} finally {
    $Response->send();
}
