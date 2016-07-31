<?php
/**
 * app/controllers/ItemsTypesController.php
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
 * Deal with ajax requests sent from the admin page
 *
 */
try {
    require_once '../../app/common.inc.php';
    $ItemsTypes = new ItemsTypes($_SESSION['team_id']);

    if (!$_SESSION['is_admin']) {
        throw new Exception('Non admin user tried to access admin panel.');
    }

    // CREATE ITEMS TYPES
    if (isset($_POST['itemsTypesCreate'])) {
        if ($ItemsTypes->create(
            $_POST['name'],
            $_POST['color'],
            $_POST['bookable'],
            $_POST['template']
        )) {
            echo '1';
        } else {
            echo '0';
        }
    }

    // UPDATE ITEM TYPE
    if (isset($_POST['itemsTypesUpdate'])) {
        if ($ItemsTypes->update(
            $_POST['id'],
            $_POST['name'],
            $_POST['color'],
            $_POST['bookable'],
            $_POST['template']
        )) {
            echo '1';
        } else {
            echo '0';
        }
    }

    // DESTROY ITEM TYPE
    if (isset($_POST['itemsTypesDestroy'])) {
        try {
            $ItemsTypes->destroy($_POST['id']);
            echo '1';
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

} catch (Exception $e) {
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
}
