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

use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Deal with things common to experiments and items like tags, uploads, quicksave and lock
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();
$Response->setData(array(
    'res' => false,
    'msg' => Tools::error()
));

try {
    // id of the item (experiment or database item)
    $id = 1;

    if ($Request->request->has('id')) {
        $id = $Request->request->get('id');
    } elseif ($Request->query->has('id')) {
        $id = $Request->query->get('id');
    }

    if ($Request->request->get('type') === 'experiments' ||
        $Request->query->get('type') === 'experiments') {
        $Entity = new Experiments($App->Users, $id);
    } elseif ($Request->request->get('type') === 'experiments_tpl') {
        $Entity = new Templates($App->Users, $id);
    } else {
        $Entity = new Database($App->Users, $id);
    }

    /**
     * GET REQUESTS
     *
     */

    // GET MENTION LIST
    if ($Request->query->has('term') && $Request->query->has('mention')) {
        $userFilter = false;
        $term = $Request->query->filter('term', null, FILTER_SANITIZE_STRING);
        if ($Request->query->has('userFilter')) {
            $userFilter = true;
        }
        $Response->setData($Entity->getMentionList($term, $userFilter));
    }

    /**
     * POST REQUESTS
     *
     */

    // UPDATE VISIBILITY
    if ($Request->request->has('updateVisibility')) {
        $Response = new JsonResponse();
        $Entity->canOrExplode('write');
        $Entity->checkVisibility($Request->request->get('visibility'));

        if ($Entity->updateVisibility($Request->request->get('visibility'))) {
            $Response->setData(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        }
    }


    // GET BODY
    if ($Request->request->has('getBody')) {
        $Entity->canOrExplode('read');
        $Response->setData(array(
            'res' => true,
            'msg' => $Entity->entityData['body']
        ));
    }

    // LOCK
    if ($Request->request->has('lock')) {
        $permissions = $Entity->getPermissions();
        // We don't have can_lock, but maybe it's our XP, so we can lock it
        if (!$App->Users->userData['can_lock'] && !$permissions['write']) {
            throw new ImproperActionException(_("You don't have the rights to lock/unlock this."));
        }
        if ($Entity->toggleLock()) {
            $Response->setData(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        }
    }

    // QUICKSAVE
    if ($Request->request->has('quickSave')) {
        $Entity->canOrExplode('write');
        if ($Entity->update(
            $Request->request->get('title'),
            $Request->request->get('date'),
            $Request->request->get('body')
        )) {
            $Response->setData(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        }
    }

    // UPDATE CATEGORY (item type or status)
    if ($Request->request->has('updateCategory')) {
        $Entity->canOrExplode('write');

        if ($Entity->updateCategory($Request->request->get('categoryId'))) {
            // get the color of the status/item type for updating the css
            if ($Entity->type === 'experiments') {
                $Category = new Status($App->Users);
            } else {
                $Category = new ItemsTypes($App->Users);
            }
            $Response->setData(array(
                'res' => true,
                'msg' => _('Saved'),
                'color' => $Category->readColor($Request->request->get('categoryId'))
            ));
        }
    }

    // UPDATE FILE COMMENT
    if ($Request->request->has('updateFileComment')) {
        $Entity->canOrExplode('write');
        $comment = $Request->request->filter('comment', null, FILTER_SANITIZE_STRING);

        if (\mb_strlen($comment) === 0 || $comment === ' ') {
            throw new ImproperActionException(_('Comment is too short'));
        }

        $id_arr = \explode('_', $Request->request->get('comment_id'));
        if (Tools::checkId((int) $id_arr[1]) === false) {
            throw new IllegalActionException('The id parameter is invalid');
        }
        $comment_id = $id_arr[1];

        if ($Entity->Uploads->updateComment((int) $comment_id, $comment)) {
            $Response->setData(array(
                'res' => true,
                'msg' => _('Saved')
            ));
        }
    }

    // CREATE UPLOAD
    if ($Request->request->has('upload')) {
        $Entity->canOrExplode('write');
        if ($Entity->Uploads->create($Request)) {
            $Response->setData(array(
                'res' => true,
                'msg' => _('Saved')
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
        $upload = $Entity->Uploads->readFromId((int) $Request->request->get('upload_id'));
        // check write permissions
        $Entity->canOrExplode('write');

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
        }
    }

    // DESTROY ENTITY
    if ($Request->request->has('destroy')) {

        // check write permissions
        $Entity->canOrExplode('write');

        // check for deletable xp
        if ($Entity instanceof Experiments && !$App->teamConfigArr['deletable_xp'] && !$Session->get('is_admin')) {
            throw new Exception(Tools::error(true));
        }

        if ($Entity->destroy()) {
            $Response->setData(array(
                'res' => true,
                'msg' => _('Item deleted successfully')
            ));
        }
    }
} catch (ImproperActionException $e) {
    $Response->setData(array(
        'res' => false,
        'msg' => $e->__toString()
    ));

} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e->__toString())));
    $Response->setData(array(
        'res' => false,
        'msg' => Tools::error(true)
    ));

} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid') ?? 'anon'), array('exception' => $e)));
    $Response->setData(array(
        'res' => false,
        'msg' => Tools::error()
    ));
} finally {
    $Response->send();
}
