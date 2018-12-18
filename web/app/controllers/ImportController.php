<?php
/**
 * app/controllers/ImportController.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Import a zip or a csv
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';

$Response = new RedirectResponse('../../admin.php');

try {
    // it might take some time and we don't want to be cut in the middle, so set time_limit to ∞
    \set_time_limit(0);

    if ($Request->request->get('type') === 'csv') {
        $Import = new ImportCsv($App->Users, $App->Request);
    } elseif ($Request->request->get('type') === 'zip') {
        $Import = new ImportZip($App->Users, $App->Request);
    } else {
        throw new IllegalActionException('Invalid argument');
    }

    $msg = $Import->inserted . ' ' .
        ngettext('item imported successfully.', 'items imported successfully.', $Import->inserted);
    $App->Session->getFlashBag()->add('ok', $msg);

} catch (ImproperActionException $e) {
    $App->Session->getFlashBag()->add('ko', $e->__toString());

} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e->__toString())));
    $App->Session->getFlashBag()->add('ko', Tools::error(true));

} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e->__toString())));
    $App->Session->getFlashBag()->add('ko', Tools::error());

} finally {
    $Response->send();
}
