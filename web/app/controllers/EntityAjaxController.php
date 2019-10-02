<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidCsrfTokenException;
use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Status;
use Elabftw\Models\Templates;
use Elabftw\Services\Check;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Deal with things common to experiments and items like tags, uploads, quicksave and lock
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();
$Response->setData(array(
    'res' => true,
    'msg' => _('Saved'),
));

try {
    // CSRF
    $App->Csrf->validate();

    // id of the item (experiment or database item)
    $id = 1;

    if ($Request->request->has('id')) {
        $id = (int) $Request->request->get('id');
    } elseif ($Request->query->has('id')) {
        $id = (int) $Request->query->get('id');
    }

    if ($Request->request->get('type') === 'experiments' ||
        $Request->query->get('type') === 'experiments') {
        $Entity = new Experiments($App->Users, $id);
    } elseif ($Request->request->get('type') === 'experiments_templates') {
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
        $term = $Request->query->filter('term', null, FILTER_SANITIZE_STRING);
        $Response->setData($Entity->getMentionList($term));
    }

    // GET BODY
    if ($Request->query->has('getBody')) {
        $Entity->canOrExplode('read');
        $body = $Entity->entityData['body'];
        if ($Request->query->get('editor') === 'tiny') {
            $body = Tools::md2html($body);
        }
        $Response->setData(array(
            'res' => true,
            'msg' => $body,
        ));
    }

    // GET LINK LIST
    if ($Request->query->has('term') && !$Request->query->has('mention')) {
        // we don't care about the entity type as getLinkList() is available in AbstractEntity
        $Entity = new Experiments($App->Users);
        $Response->setData($Entity->getLinkList($Request->query->get('term')));
    }

    /**
     * POST REQUESTS
     *
     */

    if ($Request->request->has('saveAsImage')) {
        $Entity->Uploads->createFromString('png', $Request->request->get('realName'), $Request->request->get('content'));
    }

    // CREATE STEP
    if ($Request->request->has('createStep')) {
        $Entity->Steps->create($Request->request->get('body'));
    }

    // FINISH STEP
    if ($Request->request->has('finishStep')) {
        $Entity->Steps->finish((int) $Request->request->get('stepId'));
    }

    // DESTROY STEP
    if ($Request->request->has('destroyStep')) {
        $Entity->Steps->destroy((int) $Request->request->get('stepId'));
    }

    // CREATE LINK
    if ($Request->request->has('createLink')) {
        $Entity->Links->create((int) $Request->request->get('linkId'));
    }

    // DESTROY LINK
    if ($Request->request->has('destroyLink')) {
        $Entity->Links->destroy((int) $Request->request->get('linkId'));
    }

    // UPDATE VISIBILITY
    if ($Request->request->has('updateVisibility')) {
        $Entity->updateVisibility($Request->request->get('visibility'));
    }


    // TOGGLE LOCK
    if ($Request->request->has('lock')) {
        $Entity->toggleLock();
    }

    // QUICKSAVE
    if ($Request->request->has('quickSave')) {
        $Entity->update(
            $Request->request->get('title'),
            $Request->request->get('date'),
            $Request->request->get('body')
        );
    }

    // DUPLICATE
    if ($Request->request->has('duplicate')) {
        $Entity->canOrExplode('read');
        $id = $Entity->duplicate();
        $Response->setData(array(
            'res' => true,
            'msg' => $id,
        ));
    }

    // SHARE
    if ($Request->request->has('getShareLink')) {
        if (!$Entity instanceof Experiments) {
            throw new IllegalActionException('Can only share experiments.');
        }
        $Entity->canOrExplode('read');
        $link = Tools::getUrl($Request) . '/experiments.php?mode=view&id=' . $Entity->id . '&elabid=' . $Entity->entityData['elabid'];
        $Response->setData(array(
            'res' => true,
            'msg' => $link,
        ));
    }

    // UPDATE FILE COMMENT
    if ($Request->request->has('updateFileComment')) {
        $Entity->canOrExplode('write');
        $comment = $Request->request->filter('comment', null, FILTER_SANITIZE_STRING);
        $id_arr = \explode('_', $Request->request->get('comment_id'));
        $comment_id = (int) $id_arr[1];
        if (Check::id($comment_id) === false) {
            throw new IllegalActionException('The id parameter is invalid');
        }

        $Entity->Uploads->updateComment($comment_id, $comment);
    }

    // CREATE UPLOAD
    if ($Request->request->has('upload')) {
        $Entity->Uploads->create($Request);
    }

    // ADD MOL FILE OR PNG
    if ($Request->request->has('addFromString')) {
        $Entity->Uploads->createFromString(
            $Request->request->get('fileType'),
            $Request->request->get('realName'),
            $Request->request->get('string')
        );
    }

    // DESTROY ENTITY
    if ($Request->request->has('destroy')) {

        // check for deletable xp
        if ($Entity instanceof Experiments && (!$App->teamConfigArr['deletable_xp'] && !$Session->get('is_admin'))
            || $App->Config->configArr['deletable_xp'] === '0') {
            throw new ImproperActionException('You cannot delete experiments!');
        }
        $Entity->destroy();
    }

    // UPDATE CATEGORY (item type or status)
    if ($Request->request->has('updateCategory')) {
        $Entity->updateCategory((int) $Request->request->get('categoryId'));
        // get the color of the status/item type for updating the css
        if ($Entity instanceof Experiments) {
            $Category = new Status($App->Users);
        } else {
            $Category = new ItemsTypes($App->Users);
        }
        $Response->setData(array(
            'res' => true,
            'msg' => _('Saved'),
            'color' => $Category->readColor((int) $Request->request->get('categoryId')),
        ));
    }


    // DESTROY UPLOAD
    if ($Request->request->has('uploadsDestroy')) {
        $upload = $Entity->Uploads->readFromId((int) $Request->request->get('upload_id'));
        $Entity->Uploads->destroy((int) $Request->request->get('upload_id'));
        // check that the filename is not in the body. see #432
        $msg = '';
        if (strpos($Entity->entityData['body'], $upload['long_name'])) {
            $msg = '. ';
            $msg .= _('Please make sure to remove any reference to this file in the body!');
        }
        $Response->setData(array(
            'res' => true,
            'msg' => _('File deleted successfully') . $msg,
        ));
    }
} catch (ImproperActionException | InvalidCsrfTokenException $e) {
    $Response->setData(array(
        'res' => false,
        'msg' => $e->getMessage(),
    ));
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $Response->setData(array(
        'res' => false,
        'msg' => Tools::error(true),
    ));
} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid') ?? 'anon'), array('Exception' => $e)));
    $Response->setData(array(
        'res' => false,
        'msg' => Tools::error(),
    ));
} finally {
    $Response->send();
}
