<?php
/**
 * app/controllers/TagsController.php
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
 * Tags
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';

try {
    if ($App->Session->has('anon')) {
        throw new Exception(Tools::error(true));
    }

    $Response = new JsonResponse();

    // id of the item (experiment or database item)
    $id = 1;

    if ($Request->request->has('item_id')) {
        $id = $Request->request->get('item_id');
    }

    if ($Request->request->get('type') === 'experiments' ||
        $Request->query->get('type') === 'experiments') {
        $Entity = new Experiments($App->Users, $id);
    } elseif ($Request->request->get('type') === 'experiments_tpl') {
        $Entity = new Templates($App->Users, $id);
    } else {
        $Entity = new Database($App->Users, $id);
    }

    $Tags = new Tags($Entity);

    // CREATE TAG
    if ($Request->request->has('createTag')) {
        $Entity->canOrExplode('write');
        // Sanitize tag, we remove '\' because it fucks up the javascript if you have this in the tags
        // also remove | because we use this as separator for tags in SQL
        $tag = str_replace(array('\\', '|'), array('', ' '), $Request->request->filter('tag', null, FILTER_SANITIZE_STRING));
        // empty tags are disallowed
        if ($tag === '') {
            throw new Exception(_('Tag is too short!'));
        }

        $Tags->create($tag);
    }

    // GET TAG LIST
    if ($Request->query->has('term')) {
        $term = $Request->query->filter('term', null, FILTER_SANITIZE_STRING);
        $Response->setData($Tags->getList($term));
    }

    // UPDATE TAG
    if ($Request->request->has('update') && $Session->get('is_admin')) {
        $Tags->update($Request->request->get('tag'), $Request->request->get('newtag'));
    }

    // DEDUPLICATE TAG
    if ($Request->request->has('deduplicate') && $Session->get('is_admin')) {
        $deduplicated = $Tags->deduplicate($Request->request->get('tag'));
        $Response->setData(array('res' => true, 'msg' => "Removed $deduplicated duplicates"));
    }


    // UNREFERENCE TAG
    if ($Request->request->has('unreferenceTag')) {
        if (Tools::checkId($Request->request->get('tag_id')) === false) {
            throw new Exception('Bad id value');
        }
        $Tags->unreference((int) $Request->request->get('tag_id'));
    }

    // DELETE TAG
    if ($Request->request->has('destroyTag') && $App->Session->get('is_admin')) {
        if (Tools::checkId($Request->request->get('tag_id')) === false) {
            throw new Exception('Bad id value');
        }
        try {
            $res = $Tags->destroy((int) $Request->request->get('tag_id'));
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

    $Response->send();

} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e)));
    $Session->getFlashBag()->add('ko', Tools::error());
    header('Location: ../../experiments.php');
}
