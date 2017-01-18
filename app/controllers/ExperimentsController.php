<?php
/**
 * app/controllers/ExperimentsController.php
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
 * Experiments
 *
 */
require_once '../../app/init.inc.php';

try {

    $Experiments = new Experiments($_SESSION['team_id'], $_SESSION['userid']);

    // CREATE
    if (isset($_GET['create'])) {
        if (isset($_GET['tpl']) && !empty($_GET['tpl'])) {
            $id = $Experiments->create($_GET['tpl']);
        } else {
            $id = $Experiments->create();
        }
        header("location: ../../experiments.php?mode=edit&id=" . $id);
    }

    // UPDATE
    if (isset($_POST['update'])) {
        $Experiments->setId($_POST['id'], 'experiments');

        if (!$Experiments->canWrite) {
            throw new Exception(Tools::error(true));
        }

        if ($Experiments->update(
            $_POST['title'],
            $_POST['date'],
            $_POST['body']
        )) {
            header("location: ../../experiments.php?mode=view&id=" . $_POST['id']);
        } else {
            throw new Exception('Error updating experiment');
        }
    }

    // DUPLICATE
    if (isset($_GET['duplicateId'])) {
        $Experiments->setId($_GET['duplicateId'], 'experiments');

        if (!$Experiments->canRead) {
            throw new Exception(Tools::error(true));
        }
        $id = $Experiments->duplicate();
        $mode = 'edit';
        header("location: ../../experiments.php?mode=" . $mode . "&id=" . $id);
    }

    // UPDATE STATUS
    if (isset($_POST['updateStatus'])) {
        $Experiments->setId($_POST['id'], 'experiments');

        if (!$Experiments->canWrite) {
            throw new Exception(Tools::error(true));
        }

        if ($Experiments->updateStatus($_POST['status'])) {
            // get the color of the status for updating the css
            $Status = new Status($_SESSION['team_id']);
            echo json_encode(array(
                'res' => true,
                'msg' => _('Saved'),
                'color' => $Status->readColor($_POST['status'])
            ));
        } else {
            echo json_encode(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

    // ADD MOL FILE
    if (isset($_POST['addMol'])) {
        $Uploads = new Uploads('experiments', $_POST['item']);
        echo $Uploads->createFromMol($_POST['mol']);
    }

    // UPDATE VISIBILITY
    if (isset($_POST['updateVisibility'])) {
        $Experiments->setId($_POST['id'], 'experiments');
        if ($Experiments->updateVisibility($_POST['visibility'])) {
            echo json_encode(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        } else {
            echo json_encode(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

    // CREATE LINK
    if (isset($_POST['createLink'])) {
        $Experiments->setId($_POST['id'], 'experiments');
        if ($Experiments->Links->create($_POST['linkId'])) {
            echo json_encode(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        } else {
            echo json_encode(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

    // DESTROY LINK
    if (isset($_POST['destroyLink'])) {
        $Experiments->setId($_POST['id'], 'experiments');
        if ($Experiments->Links->destroy($_POST['linkId'])) {
            echo json_encode(array(
                'res' => true,
                'msg' => _('Link deleted successfully')
            ));
        } else {
            echo json_encode(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

    // TIMESTAMP
    if (isset($_POST['timestamp'])) {
        try {
            $ts = new TrustedTimestamps(new Config(), new Teams($_SESSION['team_id']), $_POST['id']);
            if ($ts->timeStamp()) {
                echo json_encode(array(
                    'res' => true
                ));
            }
        } catch (Exception $e) {
            $Logs = new Logs();
            $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
            echo json_encode(array(
                'res' => false,
                'msg' => $e->getMessage()
            ));
        }

    }

    // DESTROY
    if (isset($_POST['destroy'])) {
        $Experiments->setId($_POST['id'], 'experiments');
        $Teams = new Teams($_SESSION['team_id']);

        if ((($Teams->read('deletable_xp') == '0') &&
            !$_SESSION['is_admin']) ||
            (!$Experiments->canWrite)) {
            echo json_encode(array(
                'res' => false,
                'msg' => _("You don't have the rights to delete this experiment.")
            ));
        } else {
            if ($Experiments->destroy()) {
                echo json_encode(array(
                    'res' => true,
                    'msg' => _('Experiment successfully deleted')
                ));
            } else {
                echo json_encode(array(
                    'res' => false,
                    'msg' => Tools::error()
                ));
            }
        }
    }

    // DECODE ASN1 TOKEN
    if (isset($_POST['asn1']) && is_readable(ELAB_ROOT . "uploads/" . $_POST['asn1'])) {
        $TrustedTimestamps = new TrustedTimestamps(new Config(), new Teams($_SESSION['team_id']), $_POST['exp_id']);

        echo json_encode(array(
            'res' => true,
            'msg' => $TrustedTimestamps->decodeAsn1($_POST['asn1'])
        ));
    }

} catch (Exception $e) {
    $Logs = new Logs();
    $Logs->create('Error', $_SESSION['userid'], $e->getMessage());
    $_SESSION['ko'][] = Tools::error();
    header('Location: ../../experiments.php');
}
