<?php
/**
 * revisions.php
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
 * Show history of body of experiment or db item
 *
 */
try {
    require_once 'app/init.inc.php';
    $pageTitle = _('Revisions');
    $selectedMenu = null;
    $errflag = false;
    require_once 'app/head.inc.php';

    $Users = new Users($_SESSION['userid']);
    if ($_GET['type'] === 'experiments') {
        $Entity = new Experiments($Users, $_GET['item_id']);
        $location = 'experiments';

    } elseif ($_GET['type'] === 'items') {

        $Entity = new Database($Users, $_GET['item_id']);
        $location = 'database';

    } else {
        throw new Exception('Bad type!');
    }

    $Entity->canOrExplode('write');
    $Revisions = new Revisions($Entity);

    // THE RESTORE ACTION
    if (isset($_GET['action']) && $_GET['action'] === 'restore') {
        $revId = Tools::checkId($_GET['rev_id']);
        if ($revId === false) {
            throw new Exception(_('The id parameter is not valid!'));
        }

        $Revisions->restore($revId);

        header("Location: " . $location . ".php?mode=view&id=" . $_GET['item_id']);
        throw new Exception('Redirect');
    }

    // BEGIN PAGE
    echo "<a href='" . $location . ".php?mode=view&id=" . $_GET['item_id'] . "'><h4><img src='app/img/undo.png' alt='<--' /> " . _('Go back') . "</h4></a>";
    $revisionArr = $Revisions->read();
    foreach ($revisionArr as $revision) {
        echo "<div class='item'>" . _('Saved on:') . " " . $revision['savedate'] . " <a href='revisions.php?item_id=" . $_GET['item_id'] . "&type=" . $_GET['type'] . "&action=restore&rev_id=" . $revision['id'] . "'>" . _('Restore') . "</a><br>";
        echo $revision['body'] . "</div>";
    }

} catch (Exception $e) {
    echo Tools::displayMessage($e->getMessage(), 'ko');
} finally {
    require_once 'app/footer.inc.php';
}
