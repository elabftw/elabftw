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

    if ($Request->request->get('type') === 'experiments') {
        $Entity = new Experiments($App->Users);
    } else {
        $Entity = new Database($App->Users);
    }

    $Tags = new Tags($Entity);

    if ($Request->request->has('update') && $Session->get('is_admin')) {
        $Tags->update($Request->request->get('tag'), $Request->request->get('newtag'));
    }

} catch (Exception $e) {
    $App->Logs->create('Error', $Session->get('userid'), $e->getMessage());
    $Session->getFlashBag()->add('ko', Tools::error());
    header('Location: ../../experiments.php');
}
