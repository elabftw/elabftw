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

use Exception;
use RuntimeException;

/**
 * Import a zip or a csv
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';

try {
    // it might take some time and we don't want to be cut in the middle, so set time_limit to ∞
    \set_time_limit(0);

    if ($Request->request->get('type') === 'csv') {
        $Import = new ImportCsv($App->Users, $App->Request);
    } elseif ($Request->request->get('type') === 'zip') {
        $Import = new ImportZip($App->Users, $App->Request);
    } else {
        throw new Exception('Invalid argument');
    }

    $msg = $Import->inserted . ' ' .
        ngettext('item imported successfully.', 'items imported successfully.', $Import->inserted);
    $Session->getFlashBag()->add('ok', $msg);

} catch (RuntimeException $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e)));
    $Session->getFlashBag()->add('ko', $e->getMessage());

} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e)));
    $Session->getFlashBag()->add('ko', Tools::error());
} finally {
    header('Location: ../../admin.php');
}
