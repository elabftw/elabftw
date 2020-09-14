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
use Elabftw\Exceptions\InvalidCsrfTokenException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Links;
use Elabftw\Models\Status;
use Elabftw\Models\Steps;
use Elabftw\Models\Tags;
use Elabftw\Models\Templates;
use Elabftw\Models\Todolist;
use Exception;
use Swift_TransportException;
use Symfony\Component\HttpFoundation\JsonResponse;

require_once dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();
$Response->setData(array(
    'res' => true,
    'msg' => _('Saved'),
));

try {
    // CSRF
    $App->Csrf->validate();

    if ($Request->getMethod() === 'POST') {
        $what = $Request->request->get('what');
        $action = $Request->request->get('action');
        $params = $Request->request->get('params') ?? array();
    } else {
        $what = $Request->query->get('what');
        $action = $Request->query->get('action');
        $params = $Request->query->get('params') ?? array();
    }

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


    switch ($what) {
        case 'comment':
            $Model = $Entity->Comments;
            break;

        case 'itemsTypes':
            // items types is only from admin panel
            if (!$App->Session->get('is_admin')) {
                throw new IllegalActionException('Non admin user tried to access admin controller.');
            }
            $Model = new ItemsTypes($App->Users);
            break;

        case 'link':
            $Model = new Links($Entity);
            break;

        case 'status':
            // status is only from admin panel
            if (!$App->Session->get('is_admin')) {
                throw new IllegalActionException('Non admin user tried to access admin controller.');
            }
            $Model = new Status($App->Users);
            break;

        case 'step':
            $Model = new Steps($Entity);
            break;

        case 'tag':
            $Model = new Tags($Entity);
            break;

        case 'template':
            $Model = $Entity;
            break;

        case 'todolist':
            $Model = new Todolist($App->Users);
            break;

        case 'user':
            $Model = $App->Users;
            break;

        default:
            throw new IllegalActionException('Bad what param on Ajax controller');
    }

    $Params = new ParamsProcessor($params);

    switch ($action) {
        case 'readAll':
            $res = $Model->readAll();
            $Response->setData(array(
                'res' => true,
                'msg' => $res,
            ));
            // no break
        case 'getList':
            $Response->setData($Model->getList($Params->name));
            break;
        case 'create':
            $res = $Model->create($Params);
            $Response->setData(array(
                'res' => true,
                'msg' => _('Saved'),
                'value' => $res,
            ));
            break;
        case 'update':
            $res = $Model->update($Params);
            $Response->setData(array(
                'res' => true,
                'msg' => _('Saved'),
                'value' => $res,
            ));
            break;
        case 'destroy':
            $Model->destroy($Params->id);
            break;
        case 'deduplicate':
            $deduplicated = $Model->deduplicate();
            $Response->setData(array('res' => true, 'msg' => sprintf(_('Deduplicated %d tags'), $deduplicated)));
            break;
        case 'duplicate':
            $Model->duplicate();
            break;
        case 'finish':
            $Model->finish($Params->id);
            break;
        case 'unreference':
            $Model->unreference($Params->id);
            break;
        default:
            throw new IllegalActionException('Bad action param on Ajax controller');
    }
} catch (Swift_TransportException $e) {
    // for swift error, don't display error to user as it might contain sensitive information
    // but log it and display general error. See #841
    $App->Log->error('', array('exception' => $e));
    $Response->setData(array(
        'res' => false,
        'msg' => _('Error sending email'),
    ));
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
