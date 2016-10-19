<?php
/**
 * app/controllers/UcpController.php
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
 * Deal with ajax requests sent from the user control panel
 *
 */
try {
    require_once '../../app/init.inc.php';

    if (Tools::checkId($_POST['id']) === false) {
        throw new Exception('The id parameter is invalid!');
    }

    $Templates = new Templates($_SESSION['team_id']);

    // TEMPLATES DESTROY
    if (isset($_POST['templatesDestroy'])) {
        if ($Templates->destroy($_POST['id'], $_SESSION['userid'])) {
            echo json_encode(array(
                'res' => true,
                'msg' => _('Template deleted successfully')
            ));
        } else {
            echo json_encode(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

} catch (Exception $e) {
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
}
