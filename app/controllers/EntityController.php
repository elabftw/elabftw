<?php
/**
 * app/controllers/EntityController.php
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
 * Deal with things common to experiments and items like tags, uploads, quicksave and lock
 *
 */
try {
    require_once '../../app/init.inc.php';

    if ($_POST['type'] === 'experiments') {
        $Entity = new Experiments($Users, $_POST['id']);
    } else {
        $Entity = new Database($Users, $_POST['id']);
    }
    // GET BODY
    if (isset($_POST['getBody'])) {
        $permissions = $Entity->getPermissions();

        if ($permissions['read'] === false) {
            throw new Exception(Tools::error(true));
        }

        echo $Entity->entityData['body'];
    }

    // LOCK
    if (isset($_POST['lock'])) {

        $permissions = $Entity->getPermissions();

        // We don't have can_lock, but maybe it's our XP, so we can lock it
        if (!$Users->userData['can_lock'] && !$permissions['write']) {
            throw new Exception(_("You don't have the rights to lock/unlock this."));
        }

        $errMsg = Tools::error();
        $res = null;
        try {
            $res = $Entity->toggleLock();
        } catch (Exception $e) {
            $errMsg = $e->getMessage();
        }

        if ($res) {
            echo json_encode(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        } else {
            echo json_encode(array(
                'res' => false,
                'msg' => $errMsg
            ));
        }
    }

    // QUICKSAVE
    if (isset($_POST['quickSave'])) {
        $title = Tools::checkTitle($_POST['title']);

        $body = Tools::checkBody($_POST['body']);

        $date = Tools::kdate($_POST['date']);

        $result = $Entity->update($title, $date, $body);

        if ($result) {
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

    // CREATE TAG
    if (isset($_POST['createTag'])) {
        // Sanitize tag, we remove '\' because it fucks up the javascript if you have this in the tags
        $tag = strtr(filter_var($_POST['tag'], FILTER_SANITIZE_STRING), '\\', '');
        // also remove | because we use this as separator for tags in SQL
        $tag = strtr($tag, '|', ' ');
        // check for string length and if user owns the experiment
        if (strlen($tag) < 1) {
            throw new Exception(_('Tag is too short!'));
        }
        $Entity->canOrExplode('write');

        $Tags = new Tags($Entity);
        $Tags->create($tag);
    }

    // DELETE TAG
    if (isset($_POST['destroyTag'])) {
        if (Tools::checkId($_POST['tag_id']) === false) {
            throw new Exception('Bad id value');
        }
        $Entity->canOrExplode('write');
        $Tags = new Tags($Entity);
        $Tags->destroy($_POST['tag_id']);
    }

    // UPDATE FILE COMMENT
    if (isset($_POST['updateFileComment'])) {
        try {
            $comment = filter_var($_POST['comment'], FILTER_SANITIZE_STRING);

            if (strlen($comment) === 0 || $comment === ' ') {
                throw new Exception(_('Comment is too short'));
            }

            $id_arr = explode('_', $_POST['comment_id']);
            if (Tools::checkId($id_arr[1]) === false) {
                throw new Exception(_('The id parameter is invalid'));
            }
            $id = $id_arr[1];

            $Upload = new Uploads($Entity);
            if ($Upload->updateComment($id, $comment)) {
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
        } catch (Exception $e) {
            echo json_encode(array(
                'res' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    // CREATE UPLOAD
    if (isset($_POST['upload'])) {
        try {
            $Uploads = new Uploads($Entity);
            if ($Uploads->create($_FILES)) {
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
        } catch (Exception $e) {
            echo json_encode(array(
                'res' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    // ADD MOL FILE OR PNG
    if (isset($_POST['addFromString'])) {
        $Uploads = new Uploads($Entity);
        $Entity->canOrExplode('write');
        if ($Uploads->createFromString($_POST['fileType'], $_POST['string'])) {
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


    // DESTROY UPLOAD
    if (isset($_POST['uploadsDestroy'])) {
        $Uploads = new Uploads($Entity);
        if ($Uploads->destroy($_POST['upload_id'])) {
            echo json_encode(array(
                'res' => true,
                'msg' => _('File deleted successfully')
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
