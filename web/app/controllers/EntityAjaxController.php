<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use function dirname;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidCsrfTokenException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Status;
use Elabftw\Models\Templates;
use Elabftw\Services\Check;
use Elabftw\Services\ListBuilder;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Deal with things common to experiments and items like tags, uploads, quicksave and lock
 *
 */
require_once dirname(__DIR__) . '/init.inc.php';

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
        $term = $Request->query->get('term');
        $ExperimentsHelper = new ListBuilder(new Experiments($App->Users));
        $DatabaseHelper = new ListBuilder(new Database($App->Users));
        // return list of itemd and experiments
        $mentionArr = array_merge($DatabaseHelper->getMentionList($term), $ExperimentsHelper->getMentionList($term));
        $Response->setData($mentionArr);
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
        // bind autocomplete targets the experiments
        if ($Request->query->get('source') === 'experiments') {
            $Entity = new Experiments($App->Users);
        } else {
            $Entity = new Database($App->Users);
        }
        $ListBuilder = new ListBuilder($Entity);
        $Response->setData($ListBuilder->getAutocomplete($Request->query->get('term')));
    }

    // GET BOUND EVENTS
    if ($Request->query->has('getBoundEvents') && $Entity instanceof Experiments) {
        $Entity->canOrExplode('read');
        $events = $Entity->getBoundEvents();
        $Response->setData(array(
            'res' => true,
            'msg' => $events,
        ));
    }

    /**
     * POST REQUESTS
     *
     */

    // SAVE AS IMAGE
    if ($Request->request->has('saveAsImage')) {
        $Entity->Uploads->createFromString('png', $Request->request->get('realName'), $Request->request->get('content'));
    }

    // TOGGLE PIN
    if ($Request->request->has('togglePin')) {
        $Entity->Pins->togglePin();
    }

    // UPDATE VISIBILITY
    if ($Request->request->has('updatePermissions')) {
        $Entity->updatePermissions($Request->request->get('rw'), $Request->request->get('value'));
    }

    // UPDATE TITLE
    if ($Request->request->has('updateTitle')) {
        $Entity->updateTitle($Request->request->get('title'));
    }

    // UPDATE DATE
    if ($Request->request->has('updateDate')) {
        $Entity->updateDate($Request->request->get('date'));
    }

    // UPDATE RATING
    if ($Request->request->has('rating') && $Entity instanceof Database) {
        $Entity->setId((int) $Request->request->get('id'));
        $Entity->updateRating((int) $Request->request->get('rating'));
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
        $idArr = \explode('_', $Request->request->get('commentId'));
        $commentId = (int) $idArr[1];
        if (Check::id($commentId) === false) {
            throw new IllegalActionException('The id parameter is invalid');
        }

        $Entity->Uploads->updateComment($commentId, $comment);
    }

    // CREATE UPLOAD
    if ($Request->request->has('upload')) {
        $Entity->Uploads->create($Request);
    }

    // REPLACE UPLOAD
    if ($Request->request->has('replace')) {
        $Entity->Uploads->replace($Request);
    }

    // ADD MOL FILE OR PNG
    if ($Request->request->has('addFromString')) {
        $uploadId = $Entity->Uploads->createFromString(
            $Request->request->get('fileType'),
            $Request->request->get('realName'),
            $Request->request->get('string')
        );
        $Response->setData(array(
            'res' => true,
            'msg' => _('File uploaded successfully'),
            'uploadId' => $uploadId,
        ));
    }

    // DESTROY ENTITY
    if ($Request->request->has('destroy')) {
        // check for deletable xp
        if ($Entity instanceof Experiments && (!$App->teamConfigArr['deletable_xp'] && !$App->Session->get('is_admin')
            || $App->Config->configArr['deletable_xp'] === '0')) {
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
        $upload = $Entity->Uploads->readFromId((int) $Request->request->get('uploadId'));
        $Entity->Uploads->destroy((int) $Request->request->get('uploadId'));
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
} catch (ImproperActionException | InvalidCsrfTokenException | UnauthorizedException $e) {
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
