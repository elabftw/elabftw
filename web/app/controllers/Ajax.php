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
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Links;
use Elabftw\Models\Status;
use Elabftw\Models\Steps;
use Elabftw\Models\Tags;
use Elabftw\Models\TeamGroups;
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

    $itemId = null;
    if ($Request->getMethod() === 'POST') {
        $what = $Request->request->get('what');
        $action = $Request->request->get('action');
        $type = $Request->request->get('type');
        $params = $Request->request->get('params') ?? array();
        if (isset($Request->request->get('params')['itemId'])) {
            $itemId = (int) $Request->request->get('params')['itemId'];
        }
    } else {
        $what = $Request->query->get('what');
        $action = $Request->query->get('action');
        $type = $Request->query->get('type');
        $params = $Request->query->get('params') ?? array();
        if (isset($Request->query->get('params')['itemId'])) {
            $itemId = (int) $Request->query->get('params')['itemId'];
        }
    }

    if ($type === 'experiments') {
        $Entity = new Experiments($App->Users, $itemId);
    } elseif ($type === 'experiments_templates') {
        $Entity = new Templates($App->Users, $itemId);
    } else {
        $Entity = new Database($App->Users, $itemId);
    }


    switch ($what) {
        case 'apikey':
            $Model = new ApiKeys($App->Users);
            break;

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

        case 'teamgroup':
            if (!$App->Session->get('is_admin')) {
                throw new IllegalActionException('Non admin user tried to access admin controller.');
            }
            $Model = new TeamGroups($App->Users);
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

        case 'upload':
            $Model = $Entity->Uploads;
            break;

        case 'user':
            $Model = $App->Users;
            break;

        default:
            throw new IllegalActionException('Bad what param on Ajax controller');
    }

    $Params = new ParamsProcessor($params);

    switch ($action) {
        case 'readForTinymce':
            $templates = $Model->readForUser();
            $res = array();
            foreach ($templates as $template) {
                $res[] = array('title' => $template['name'], 'description' => '', 'content' => $template['body']);
            }
            $Response->setData($res);
            break;

        case 'read':
            $res = $Model->read();
            $Response->setData(array(
                'res' => true,
                'msg' => $res,
            ));
            break;

        case 'readAll':
            $res = $Model->readAll();
            $Response->setData(array(
                'res' => true,
                'msg' => $res,
            ));
            break;

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

        case 'updateMember':
            $Model->updateMember(
                (int) $Request->request->get('params')['user'],
                (int) $Request->request->get('params')['group'],
                $Request->request->get('params')['how'],
            );
            break;

        case 'updateCommon':
            // update the common template
            $Model->updateCommon($Params->template);
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
