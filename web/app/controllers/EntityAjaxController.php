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
use Elabftw\Enums\FileFromString;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Factories\EntityFactory;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Status;
use Elabftw\Services\ListBuilder;
use Exception;
use function mb_convert_encoding;
use PDOException;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Deal with things common to experiments and items like tags, uploads, quicksave and lock
 * @deprecated new code should use proper json payload on requesthandler
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

    // can come from get or post
    $type = $Request->query->get('type');
    if ($Request->getMethod() === 'POST') {
        $type = $Request->request->get('type');
    }
    $Entity = (new EntityFactory($App->Users, (string) $type, $id))->getEntity();

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

    // GET LINK LIST
    if ($Request->query->has('term') && !$Request->query->has('mention')) {
        $catFilter = (int) $Request->query->get('filter');
        $ListBuilder = new ListBuilder($Entity, $catFilter);
        // fix issue with Malformed UTF-8 characters, possibly incorrectly encoded
        // see #2404
        $responseArr = $ListBuilder->getAutocomplete($Request->query->get('term'));
        $Response->setData(mb_convert_encoding($responseArr, 'UTF-8', 'UTF-8'));
    }

    /**
     * POST REQUESTS
     *
     */

    // CREATE FILE ATTACHMENT FROM STRING
    if ($Request->request->has('addFromString')) {
        $uploadId = $Entity->Uploads->createFromString(
            FileFromString::from($Request->request->get('fileType')),
            $Request->request->get('realName'),
            $Request->request->get('content'),
        );
        $Response->setData(array(
            'res' => true,
            'msg' => _('File uploaded successfully'),
            'uploadId' => $uploadId,
        ));
    }

    // UPDATE VISIBILITY
    if ($Request->request->has('updatePermissions')) {
        $Entity->updatePermissions($Request->request->get('rw'), $Request->request->get('value'));
    }

    // CREATE UPLOAD
    if ($Request->request->has('upload')) {
        $realName = $Request->files->get('file')->getClientOriginalName();
        $filePath = $Request->files->get('file')->getPathname();
        $Entity->Uploads->create(new CreateUpload($realName, $filePath));
    }

    // UPDATE CATEGORY (item type or status)
    if ($Request->request->has('updateCategory')) {
        $id = (int) $Request->request->get('categoryId');
        $Entity->updateCategory($id);
        // get the color of the status/item type for updating the css
        if ($Entity instanceof Experiments) {
            $Category = new Status($App->Users->team, $id);
        } else {
            $Category = new ItemsTypes($App->Users, $id);
        }
        $category = $Category->readOne();
        $Response->setData(array(
            'res' => true,
            'msg' => _('Saved'),
            'color' => $category['color'],
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
