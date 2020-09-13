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
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Status;
use Elabftw\Models\Tags;
use Elabftw\Models\Templates;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

require_once dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();
$Response->setData(array(
    'res' => true,
    'msg' => _('Saved'),
));

try {
    if ($Request->getMethod() === 'POST') {
        $what = $Request->request->get('what');
        $action = $Request->request->get('action');
        $params = $Request->request->get('params') ?? array();
    } else {
        $what = $Request->query->get('what');
        $action = $Request->query->get('action');
        $params = $Request->query->get('params') ?? array();
    }

    switch ($what) {

        case 'status':
            // status is only from admin panel
            if (!$App->Session->get('is_admin')) {
                throw new IllegalActionException('Non admin user tried to access admin controller.');
            }
            $Model = new Status($App->Users);
            break;

        case 'itemsTypes':
            // items types is only from admin panel
            if (!$App->Session->get('is_admin')) {
                throw new IllegalActionException('Non admin user tried to access admin controller.');
            }
            $Model = new ItemsTypes($App->Users);
            break;

        case 'tag':
            $id = null;
            if (isset($Request->request->get('params')['itemId'])) {
                $id = (int) $Request->request->get('params')['itemId'];
            }
            if ($Request->request->get('type') === 'experiments') {
                $Entity = new Experiments($App->Users, $id);
            } elseif ($Request->request->get('type') === 'experiments_templates') {
                $Entity = new Templates($App->Users, $id);
            } else {
                $Entity = new Database($App->Users, $id);
            }
            $Model = new Tags($Entity);
            break;
        case 'user':
            $Model = $App->Users;
            break;

        default:
            throw new IllegalActionException('Bad what param on AdminAjaxController');
    }

    $Params = new ParamsProcessor($params);

    switch ($action) {
        case 'getList':
            $Response->setData($Model->getList($Params->name));
            break;
        case 'create':
            $Model->create($Params);
            break;
        case 'update':
            $Model->update($Params);
            break;
        case 'destroy':
            $Model->destroy($Params->id);
            break;
        case 'deduplicate':
            $deduplicated = $Model->deduplicate();
            $Response->setData(array('res' => true, 'msg' => sprintf(_('Deduplicated %d tags'), $deduplicated)));
            break;
        case 'unreference':
            $Model->unreference($Params->id);
            break;
        default:
            throw new IllegalActionException('Bad action param on AdminAjaxController');
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
} catch (DatabaseErrorException | FilesystemErrorException $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $Response->setData(array(
        'res' => false,
        'msg' => $e->getMessage(),
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
