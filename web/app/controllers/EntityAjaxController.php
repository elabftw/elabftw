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
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Status;
use Elabftw\Models\Teams;
use Elabftw\Models\Templates;
use Elabftw\Services\ListBuilder;
use Elabftw\Services\MakeBloxberg;
use Elabftw\Services\MakeTimestamp;
use Exception;
use GuzzleHttp\Client;
use function mb_convert_encoding;
use PDOException;
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
    // id of the item (experiment or database item)
    $id = null;

    if ($Request->request->has('id')) {
        $id = (int) $Request->request->get('id');
    } elseif ($Request->query->has('id')) {
        $id = (int) $Request->query->get('id');
    }

    /**
     * TODO replace block below with this
    $Processor = new RequestProcessor($App->Users, $Request);
    $Model = $Processor->getModel();
     */
    if ($Request->request->get('type') === 'experiments' ||
        $Request->query->get('type') === 'experiments' ||
        $Request->request->get('type') === 'experiment' ||
        $Request->query->get('type') === 'experiment') {
        $Entity = new Experiments($App->Users, $id);
    } elseif ($Request->request->get('type') === 'experiments_templates') {
        $Entity = new Templates($App->Users, $id);
    } else {
        $Entity = new Items($App->Users, $id);
    }

    /**
     * GET REQUESTS
     *
     */

    // GET MENTION LIST
    if ($Request->query->has('term') && $Request->query->has('mention')) {
        $term = $Request->query->get('term');
        $ExperimentsHelper = new ListBuilder(new Experiments($App->Users));
        $DatabaseHelper = new ListBuilder(new Items($App->Users));
        // return list of itemd and experiments
        $mentionArr = array_merge($DatabaseHelper->getMentionList($term), $ExperimentsHelper->getMentionList($term));
        // fix issue with Malformed UTF-8 characters, possibly incorrectly encoded
        // see #2404
        $mentionArr = mb_convert_encoding($mentionArr, 'UTF-8', 'UTF-8');
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
            $Entity = new Items($App->Users);
        }
        $ListBuilder = new ListBuilder($Entity);
        // fix issue with Malformed UTF-8 characters, possibly incorrectly encoded
        // see #2404
        $responseArr = $ListBuilder->getAutocomplete($Request->query->get('term'));
        $Response->setData(mb_convert_encoding($responseArr, 'UTF-8', 'UTF-8'));
    }

    // SHARE
    if ($Request->query->has('getShareLink')) {
        if (!($Entity instanceof Experiments || $Entity instanceof Items)) {
            throw new IllegalActionException('Can only share experiments or items.');
        }
        $Entity->canOrExplode('read');
        $link = Tools::getUrl($Request) . '/' . $Entity->page . '.php?mode=view&id=' . $Entity->id . '&elabid=' . $Entity->entityData['elabid'];
        $Response->setData(array(
            'res' => true,
            'msg' => $link,
        ));
    }

    /**
     * POST REQUESTS
     *
     */

    // TIMESTAMP
    if ($Request->request->has('timestamp') && $Entity instanceof Experiments) {
        $MakeTimestamp = new MakeTimestamp($App->Config, new Teams($App->Users), $Entity);
        $MakeTimestamp->timestamp();
    }

    // BLOXBERG
    if ($Request->request->has('bloxberg')) {
        $Make = new MakeBloxberg(new Client(), $Entity);
        $Response->setData(array(
            'res' => $Make->timestamp(),
            'msg' => _('Saved'),
        ));
    }

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

    // CREATE UPLOAD
    if ($Request->request->has('upload')) {
        $Entity->Uploads->create(new CreateUpload($Request));
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

    // UPDATE CATEGORY (item type or status)
    if ($Request->request->has('updateCategory')) {
        $Entity->updateCategory((int) $Request->request->get('categoryId'));
        // get the color of the status/item type for updating the css
        if ($Entity instanceof Experiments) {
            $Category = new Status($App->Users->team);
        } else {
            $Category = new ItemsTypes($App->Users);
        }
        $Response->setData(array(
            'res' => true,
            'msg' => _('Saved'),
            'color' => $Category->readColor((int) $Request->request->get('categoryId')),
        ));
    }
} catch (ImproperActionException | UnauthorizedException | PDOException $e) {
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
