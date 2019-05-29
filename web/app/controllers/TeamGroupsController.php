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
use Elabftw\Models\TeamGroups;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deal with ajax requests sent from the admin page
 */
require_once \dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();
// default response is general error
$Response->setData(array(
    'res' => true,
    'msg' => _('Saved'),
));

try {
    if (!$App->Session->get('is_admin')) {
        throw new IllegalActionException('Non admin user tried to access team groups controller.');
    }

    $TeamGroups = new TeamGroups($App->Users);

    // CREATE TEAM GROUP
    if ($Request->request->has('teamGroupCreate')) {
        $TeamGroups->create($Request->request->filter('teamGroupCreate', null, FILTER_SANITIZE_STRING));
    }

    // EDIT TEAM GROUP NAME FROM JEDITABLE
    if ($Request->request->has('teamGroupUpdateName')) {
        // the output is echoed so it gets back into jeditable input field
        $name = $TeamGroups->update(
            $Request->request->filter('teamGroupUpdateName', null, FILTER_SANITIZE_STRING),
            $Request->request->get('id')
        );
        $Response = new Response();
        $Response->prepare($Request);
        $Response->setContent($name);
    }

    // ADD OR REMOVE USER TO/FROM TEAM GROUP
    if ($Request->request->has('teamGroupUser')) {
        $TeamGroups->updateMember(
            (int) $Request->request->get('teamGroupUser'),
            (int) $Request->request->get('teamGroupGroup'),
            $Request->request->get('action')
        );
    }

    // DESTROY TEAM GROUP
    if ($Request->request->has('teamGroupDestroy')) {
        $TeamGroups->destroy((int) $Request->request->get('teamGroupGroup'));
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
