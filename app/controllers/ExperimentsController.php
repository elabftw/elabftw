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

/**
 * Experiments
 *
 */
require_once '../../inc/common.php';

try {
    $experiments = new \Elabftw\Elabftw\Experiments($_POST['experimentsId'], $_SESSION['userid']);

    // UPDATE
    if (isset($_POST['experimentsUpdate'])) {
        if ($experiments->update(
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

    // UPDATE STATUS
    if (isset($_POST['experimentsUpdateStatus'])) {
        echo $experiments->updateStatus($_POST['experimentsUpdateStatusStatus']);
    }

    // UPDATE VISIBILITY
    if (isset($_POST['experimentsUpdateVisibility'])) {
        if ($experiments->updateVisibility(
            $_POST['experimentsUpdateVisibilityVisibility']))
        {
            echo '1';
        } else {
            echo '0';
        }
    }

    // CREATE LINK
    if (isset($_POST['experimentsCreateLink'])) {
        if ($experiments->createLink($_POST['experimentsCreateLinkId']))
        {
            echo '1';
        } else {
            echo '0';
        }
    }

    // DESTROY LINK
    if (isset($_POST['experimentsDestroyLink'])) {
        if ($experiments->destroyLink($_POST['experimentsDestroyLinkId']))
        {
            echo '1';
        } else {
            echo '0';
        }
    }

} catch (Exception $e) {
    dblog('Error', $_SESSION['userid'], $e->getMessage());
}
