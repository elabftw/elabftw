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
use Elabftw\Factories\EntityFactory;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Status;
use Elabftw\Services\ListBuilder;
use Exception;
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
        // return list of itemd and experiments
        $Response->setData(array_merge(
            (new ListBuilder(new Items($App->Users)))->getMentionList($term),
            (new ListBuilder(new Experiments($App->Users)))->getMentionList($term)
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
