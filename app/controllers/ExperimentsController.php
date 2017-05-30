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
try {
    require_once '../../app/init.inc.php';

    $Entity = new Experiments($Users);

    // CREATE
    if (isset($_GET['create'])) {
        if (isset($_GET['tpl']) && !empty($_GET['tpl'])) {
            $id = $Entity->create($_GET['tpl']);
        } else {
            $id = $Entity->create();
        }
        header("location: ../../experiments.php?mode=edit&id=" . $id);
    }

    // UPDATE
    if (isset($_POST['update'])) {
        $Entity->setId($_POST['id']);
        $Entity->canOrExplode('write');

        if ($Entity->update(
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
        $Entity->setId($_GET['duplicateId']);
        $Entity->canOrExplode('read');

        $id = $Entity->duplicate();
        $mode = 'edit';
        header("location: ../../experiments.php?mode=" . $mode . "&id=" . $id);
    }

    // UPDATE STATUS
    if (isset($_POST['updateStatus'])) {
        $Entity->setId($_POST['id']);
        $Entity->canOrExplode('write');


        if ($Entity->updateStatus($_POST['statusId'])) {
            // get the color of the status for updating the css
            $Status = new Status($Users);
            echo json_encode(array(
                'res' => true,
                'msg' => _('Saved'),
                'color' => $Status->readColor($_POST['statusId'])
            ));
        } else {
            echo json_encode(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

    // UPDATE VISIBILITY
    if (isset($_POST['updateVisibility'])) {
        $Entity->setId($_POST['id']);
        $Entity->canOrExplode('write');

        if (!$Entity->checkVisibility($_POST['visibility'])) {
            throw new Exception('Bad visibility argument');
        }

        if ($Entity->updateVisibility($_POST['visibility'])) {
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
        $Entity->setId($_POST['id']);
        $Entity->canOrExplode('write');

        if ($Entity->Links->create($_POST['linkId'])) {
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
        $Entity->setId($_POST['id']);
        $Entity->canOrExplode('write');

        if ($Entity->Links->destroy($_POST['linkId'])) {
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

    // GET LINK LIST
    if (isset($_GET['term'])) {
        echo json_encode($Entity->getLinkList($_GET['term']));
    }

    // TIMESTAMP
    if (isset($_POST['timestamp'])) {
        try {
            $Entity->setId($_POST['id']);
            $Entity->canOrExplode('write');
            if ($Entity->isTimestampable()) {
                $ts = new TrustedTimestamps(new Config(), new Teams($_SESSION['team_id']), $Entity);
                if ($ts->timeStamp()) {
                    echo json_encode(array(
                        'res' => true
                    ));
                }
            } else {
                echo json_encode(array(
                    'res' => false,
                    'msg' => _('This experiment cannot be timestamped!')
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
        $Entity->setId($_POST['id']);
        $Entity->canOrExplode('write');

        $Teams = new Teams($Entity->team);

        if (($Teams->read('deletable_xp') == '0') && !$_SESSION['is_admin']) {
            echo json_encode(array(
                'res' => false,
                'msg' => _("You don't have the rights to delete this experiment.")
            ));
        } else {
            if ($Entity->destroy()) {
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
        $Entity->setId($_POST['exp_id']);
        $TrustedTimestamps = new TrustedTimestamps(new Config(), new Teams($_SESSION['team_id']), $Entity);

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
