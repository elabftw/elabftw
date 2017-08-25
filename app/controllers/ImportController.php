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

/**
 * Import a zip or a csv
 *
 */
try {
    require_once '../../app/init.inc.php';
    // it might take some time and we don't want to be cut in the middle, so set time_limit to ∞
    set_time_limit(0);

    if ($Request->request->get('type') === 'csv') {
        $Import = new ImportCsv($Users);
    } elseif ($Request->request->get('type') === 'zip') {
        $Import = new ImportZip($Users);
    } else {
        throw new Exception('Invalid argument');
    }

    $msg = $Import->inserted . ' ' .
        ngettext('item imported successfully.', 'items imported successfully.', $Import->inserted);
    $Session->getFlashBag()->add('ok', $msg);

} catch (Exception $e) {
    $App->Logs->create('Error', $Session->get('userid'), $e->getMessage());
    $Session->getFlashBag()->add('ko', Tools::error());
} finally {
    header('Location: ../../admin.php');
}
