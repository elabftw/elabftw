<?php
/**
 * app/import.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
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
    require_once '../inc/common.php';
    // it might take some time and we don't want to be cut in the middle, so set time_limit to ∞
    set_time_limit(0);

    if ($_POST['type'] === 'csv') {
        $Import = new ImportCsv();
    } elseif ($_POST['type'] === 'zip') {
        $Import = new ImportZip();
    } else {
        throw new Exception('Invalid argument');
    }

    $msg = $Import->inserted . ' ' .
        ngettext('item imported successfully.', 'items imported successfully.', $Import->inserted);
    $_SESSION['ok'][] = $msg;

} catch (Exception $e) {
    $_SESSION['ko'][] = Tools::error();
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());

} finally {
    header('Location: ../admin.php');
}
