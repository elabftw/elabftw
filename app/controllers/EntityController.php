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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Deal with things common to experiments and items like tags, uploads, quicksave and lock
 *
 */
try {
    require_once '../../app/init.inc.php';

    $Response = new JsonResponse();
    // id of the item (experiment or database item)
    $id = 1;

    if ($Request->request->has('id')) {
        $id = $Request->request->get('id');
    } elseif ($Request->query->has('id')) {
        $id = $Request->query->get('id');
    }

    if ($Request->request->get('type')  === 'experiments' ||
        $Request->query->get('type') === 'experiments') {
        $Entity = new Experiments($Users, $id);
    } elseif ($Request->request->get('type') === 'tpl') {
        $Entity = new Templates($Users, $id);
    } else {
        $Entity = new Database($Users, $id);
    }

    /**
     * GET REQUESTS
     *
     */

    // GET TAG LIST
    if ($Request->query->has('term') && $Request->query->has('tag')) {
        $term = $Request->query->filter('term', null, FILTER_SANITIZE_STRING);
        $Response->setData($Entity->Tags->getList($term));
    }

    // GET MENTION LIST
    if ($Request->query->has('term') && $Request->query->has('mention')) {
        $userFilter = false;
        $term = $Request->query->filter('term', null, FILTER_SANITIZE_STRING);
        if ($Request->query->has('userFilter')) {
            $userFilter = true;
        }
        $Response->setData($Entity->getMentionList($term, $userFilter));
    }

    // DUPLICATE
    if ($Request->query->has('duplicate')) {
        $Entity->canOrExplode('read');
        $id = $Entity->duplicate();
        $Response = new RedirectResponse("../../" . $Entity->page . ".php?mode=edit&id=" . $id);
    }

    /**
     * POST REQUESTS
     *
     */

    // GET BODY
    if ($Request->request->has('getBody')) {
        $permissions = $Entity->getPermissions();
        if ($permissions['read'] === false) {
            throw new Exception(Tools::error(true));
        }
        $Response->setData(array(
            'res' => true,
            'msg' => $Entity->entityData['body']
        ));
    }

    // LOCK
    if ($Request->request->has('lock')) {
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
            $Response->setData(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        } else {
            $Response->setData(array(
                'res' => false,
                'msg' => $errMsg
            ));
        }
    }

    // UPDATE
    if ($Request->request->has('update')) {
        $Entity->canOrExplode('write');

        if ($Entity->update(
            $Request->request->get('title'),
            $Request->request->get('date'),
            $Request->request->get('body')
        )) {
            $Response = new RedirectResponse(
                "../../" . $Entity->page . ".php?mode=view&id=" . $Request->request->get('id')
            );
        } else {
            throw new Exception('Error during save.');
        }
    }

    // QUICKSAVE
    if ($Request->request->has('quickSave')) {
        if ($Entity->update(
            $Request->request->get('title'),
            $Request->request->get('date'),
            $Request->request->get('body')
        )) {
            $Response->setData(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        } else {
            $Response->setData(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

    // UPDATE CATEGORY (item type or status)
    if ($Request->request->has('updateCategory')) {
        $Response = new JsonResponse();
        $Entity->canOrExplode('write');

        if ($Entity->updateCategory($Request->request->get('categoryId'))) {
            // get the color of the status/item type for updating the css
            if ($Entity->type === 'experiments') {
                $Category = new Status($Users);
            } else {
                $Category = new ItemsTypes($Users);
            }
            $Response->setData(array(
                'res' => true,
                'msg' => _('Saved'),
                'color' => $Category->readColor($Request->request->get('categoryId'))
            ));
        } else {
            $Response->setData(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

    // CREATE TAG
    if ($Request->request->has('createTag')) {
        $Entity->canOrExplode('write');
        // Sanitize tag, we remove '\' because it fucks up the javascript if you have this in the tags
        $tag = strtr($Request->request->filter('tag', null, FILTER_SANITIZE_STRING), '\\', '');
        // also remove | because we use this as separator for tags in SQL
        $tag = strtr($tag, '|', ' ');
        // check for string length and if user owns the experiment
        if (strlen($tag) < 1) {
            throw new Exception(_('Tag is too short!'));
        }

        $Entity->Tags->create($tag);
    }

    // DELETE TAG
    if ($Request->request->has('destroyTag')) {
        if (Tools::checkId($Request->request->get('tag_id')) === false) {
            throw new Exception('Bad id value');
        }
        $Entity->canOrExplode('write');
        $Entity->Tags->destroy($Request->request->get('tag_id'));
    }

    // UPDATE FILE COMMENT
    if ($Request->request->has('updateFileComment')) {
        try {
            $comment = $Request->request->filter('comment', null, FILTER_SANITIZE_STRING);

            if (strlen($comment) === 0 || $comment === ' ') {
                throw new Exception(_('Comment is too short'));
            }

            $id_arr = explode('_', $Request->request->get('comment_id'));
            if (Tools::checkId($id_arr[1]) === false) {
                throw new Exception(_('The id parameter is invalid'));
            }
            $id = $id_arr[1];

            if ($Entity->Uploads->updateComment($id, $comment)) {
                $Response->setData(array(
                    'res' => true,
                    'msg' => _('Saved')
                ));
            } else {
                $Response->setData(array(
                    'res' => false,
                    'msg' => Tools::error()
                ));
            }
        } catch (Exception $e) {
            $Response->setData(array(
                'res' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    // CREATE UPLOAD
    if ($Request->request->has('upload')) {
        try {
            if ($Entity->Uploads->create($Request)) {
                $Response->setData(array(
                    'res' => true,
                    'msg' => _('Saved')
                ));
            } else {
                $Response->setData(array(
                    'res' => false,
                    'msg' => Tools::error()
                ));
            }
        } catch (Exception $e) {
            $Response->setData(array(
                'res' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    // ADD MOL FILE OR PNG
    if ($Request->request->has('addFromString')) {
        $Entity->canOrExplode('write');
        if ($Entity->Uploads->createFromString($Request->request->get('fileType'), $Request->request->get('string'))) {
            $Response->setData(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        } else {
            $Response->setData(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }


    // DESTROY UPLOAD
    if ($Request->request->has('uploadsDestroy')) {
        $upload = $Entity->Uploads->readFromId($Request->request->get('upload_id'));
        $permissions = $Entity->getPermissions();
        if ($permissions['write']) {
            if ($Entity->Uploads->destroy($Request->request->get('upload_id'))) {
                // check that the filename is not in the body. see #432
                $msg = "";
                if (strpos($Entity->entityData['body'], $upload['long_name'])) {
                    $msg = ". ";
                    $msg .= _("Please make sure to remove any reference to this file in the body!");
                }
                $Response->setData(array(
                    'res' => true,
                    'msg' => _('File deleted successfully') . $msg
                ));
            } else {
                $Response->setData(array(
                    'res' => false,
                    'msg' => Tools::error()
                ));
            }
        } else {
            $Response->setData(array(
                'res' => false,
                'msg' => Tools::error(true)
            ));
        }
    }

    // DESTROY ENTITY
    if ($Request->request->has('destroy')) {
        $Response = new JsonResponse();
        if ($Entity->destroy()) {
            $Response->setData(array(
                'res' => true,
                'msg' => _('Item deleted successfully')
            ));
        } else {
            $Response->setData(array(
                'res' => false,
                'msg' => Tools::error()
            ));
        }
    }

    $Response->send();

} catch (Exception $e) {
    $App->Logs->create('Error', $Session->get('userid'), $e->getMessage());
}
