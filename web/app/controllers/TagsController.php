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

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\Tags;
use Elabftw\Models\Templates;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Tags
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();
$Response->setData(array(
    'res' => true,
    'msg' => _('Saved'),
));

try {
    if ($App->Session->has('anon')) {
        throw new IllegalActionException('Anonymous user tried to access database controller.');
    }

    // id of the item (experiment or database item)
    $id = 1;

    if ($Request->request->has('item_id')) {
        $id = (int) $Request->request->get('item_id');
    }

    if ($Request->request->get('type') === 'experiments' ||
        $Request->query->get('type') === 'experiments') {
        $Entity = new Experiments($App->Users, $id);
    } elseif ($Request->request->get('type') === 'experiments_templates') {
        $Entity = new Templates($App->Users, $id);
    } else {
        $Entity = new Database($App->Users, $id);
    }

    $Tags = new Tags($Entity);

    // CREATE TAG
    if ($Request->request->has('createTag')) {
        $Tags->create($Request->request->get('tag'));
    }

    // GET TAG LIST
    if ($Request->query->has('term')) {
        $term = $Request->query->filter('term', null, FILTER_SANITIZE_STRING);
        $Response->setData($Tags->getList($term));
    }

    // UPDATE TAG
    if ($Request->request->has('update') && $App->Session->get('is_admin')) {
        $Tags->update($Request->request->get('tag'), $Request->request->get('newtag'));
    }

    // DEDUPLICATE TAG
    if ($Request->request->has('deduplicate') && $Session->get('is_admin')) {
        $deduplicated = $Tags->deduplicate($Request->request->get('tag'));
        $Response->setData(array('res' => true, 'msg' => "Removed $deduplicated duplicates"));
    }

    // UNREFERENCE TAG
    if ($Request->request->has('unreferenceTag')) {
        if (Tools::checkId((int) $Request->request->get('tag_id')) === false) {
            throw new IllegalActionException('Bad id value');
        }
        $Tags->unreference((int) $Request->request->get('tag_id'));
    }

    // DELETE TAG
    if ($Request->request->has('destroyTag') && $App->Session->get('is_admin')) {
        if (Tools::checkId((int) $Request->request->get('tag_id')) === false) {
            throw new IllegalActionException('Bad id value');
        }
        $Tags->destroy((int) $Request->request->get('tag_id'));
    }
} catch (ImproperActionException $e) {
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
} catch (DatabaseErrorException | FilesystemErrorException $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $Response->setData(array(
        'res' => false,
        'msg' => $e->getMessage(),
    ));
} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e)));
} finally {
    $Response->send();
}
