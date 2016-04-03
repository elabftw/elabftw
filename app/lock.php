<?php
/**
 * app/lock.php
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

    if ($_GET['type'] === 'experiments') {
        $Entity = new Experiments($_SESSION['userid'], $_GET['id']);
    } else {
        $Entity = new Database($_SESSION['team_id'], $_GET['id']);
    }

    if (!$Entity->toggleLock()) {
        throw new Exception('Error locking an item');
    }

} catch (Exception $e) {
    $_SESSION['ko'][] = $e->getMessage();
} finally {
    header("Location: ../" . $_GET['type'] . ".php?mode=view&id=" . $_GET['id']);
}
