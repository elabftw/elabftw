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

/**
 * Deal with ajax requests sent from the admin page
 *
 */
require_once '../../inc/common.php';

// the constructor will check for admin rights
try {
    $itemsTypes = new \Elabftw\Elabftw\ItemsTypes();

    // CREATE ITEMS TYPES
    if (isset($_POST['itemsTypesCreate'])) {
        $itemsTypes->create(
            $_POST['itemsTypesName'],
            $_POST['itemsTypesColor'],
            $_POST['itemsTypesTemplate'],
            $_SESSION['team_id']
        );
    }

    // UPDATE ITEM TYPE
    if (isset($_POST['itemsTypesUpdate'])) {
        $itemsTypes->update(
            $_POST['itemsTypesId'],
            $_POST['itemsTypesName'],
            $_POST['itemsTypesColor'],
            $_POST['itemsTypesTemplate'],
            $_SESSION['team_id']
        );
    }

} catch (Exception $e) {
    dblog('Error', $_SESSION['userid'], $e->getMessage());
}
