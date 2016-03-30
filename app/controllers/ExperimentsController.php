<?php
/**
 * app/controllers/ExperimentsController.php
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
 * Experiments
 *
 */
require_once '../../inc/common.php';

try {

    // CREATE
    if (isset($_GET['experimentsCreate'])) {
        $Experiments = new Experiments($_SESSION['userid']);
        if (isset($_GET['tpl']) && !empty($_GET['tpl'])) {
            $id = $Experiments->create($_GET['tpl']);
        } else {
            $id = $Experiments->create();
        }
        header("location: ../../experiments.php?mode=edit&id=" . $id);
        exit;
    }

    // UPDATE
    if (isset($_POST['experimentsUpdate'])) {
        $Experiments = new Experiments($_SESSION['userid'], $_POST['experimentsId']);
        if ($Experiments->update(
            $_POST['experimentsUpdateTitle'],
            $_POST['experimentsUpdateDate'],
            $_POST['experimentsUpdateBody']
        )) {
            echo 'ok';
            header("location: ../../experiments.php?mode=view&id=" . $_POST['experimentsId']);
        } else {
            die(sprintf(_("There was an unexpected problem! Please %sopen an issue on GitHub%s if you think this is a bug."), "<a href='https://github.com/elabftw/elabftw/issues/'>", "</a>"));
        }
    }

    // DUPLICATE
    if (isset($_GET['experimentsDuplicateId'])) {
        $Experiments = new Experiments($_SESSION['userid'], $_GET['experimentsDuplicateId']);
        $id = $Experiments->duplicate();
        $mode = 'edit';
        header("location: ../../experiments.php?mode=" . $mode . "&id=" . $id);
    }

    // UPDATE STATUS
    if (isset($_POST['experimentsUpdateStatus'])) {
        $Experiments = new Experiments($_SESSION['userid'], $_POST['experimentsId']);
        echo $Experiments->updateStatus($_POST['experimentsUpdateStatusStatus']);
    }

    // UPDATE VISIBILITY
    if (isset($_POST['experimentsUpdateVisibility'])) {
        $Experiments = new Experiments($_SESSION['userid'], $_POST['experimentsId']);
        if ($Experiments->updateVisibility($_POST['experimentsUpdateVisibilityVisibility'])) {
            echo '1';
        } else {
            echo '0';
        }
    }

    // CREATE LINK
    if (isset($_POST['experimentsCreateLink'])) {
        $Experiments = new Experiments($_SESSION['userid'], $_POST['experimentsId']);
        if ($Experiments->Links->create($_POST['experimentsCreateLinkId'])) {
            echo '1';
        } else {
            echo '0';
        }
    }

    // DESTROY LINK
    if (isset($_POST['experimentsDestroyLink'])) {
        $Experiments = new Experiments($_SESSION['userid'], $_POST['experimentsId']);
        if ($Experiments->Links->destroy($_POST['experimentsDestroyLinkId'])) {
            echo '1';
        } else {
            echo '0';
        }
    }

    // DESTROY
    if (isset($_POST['experimentsDestroy'])) {
        $Experiments = new Experiments($_SESSION['userid'], $_POST['experimentsId']);
        if ($Experiments->destroy()) {
            echo '1';
        } else {
            echo '0';
        }
    }

} catch (Exception $e) {
    dblog('Error', $_SESSION['userid'], $e->getMessage());
}
