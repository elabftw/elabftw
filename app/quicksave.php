<?php
/**
 * app/quicksave.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

require_once '../inc/common.php';

try {

    $title = Tools::checkTitle($_POST['title']);

    $body = Tools::checkBody($_POST['body']);

    $date = Tools::kdate($_POST['date']);

    if ($_POST['type'] == 'experiments') {

        $Experiments = new Experiments($_SESSION['userid'], $_POST['id']);
        $result = $Experiments->update($title, $date, $body);

    } elseif ($_POST['type'] == 'items') {

        $Database = new Database($_SESSION['team_id'], $_POST['id']);
        $result = $Database->update($title, $date, $body, $_SESSION['userid']);
    }

    if ($result) {
        echo 1;
    } else {
        echo 0;
    }

} catch (Exception $e) {
    echo $e->getMessage();
}
