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
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Status;
use Elabftw\Models\Tags;
use Elabftw\Models\Teams;
use Exception;
use PDOException;
use Swift_TransportException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

require_once dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();
$Response->setData(array(
    'res' => false,
    'msg' => Tools::error(),
));
$res = '';

try {
    if ($Request->headers->get('Content-Type') === 'application/json') {
        $Processor = new JsonProcessor($App->Users, $Request);
    } elseif ($Request->getMethod() === 'GET') {
        $Processor = new RequestProcessor($App->Users, $Request);
    } else {
        $Processor = new FormProcessor($App->Users, $Request);
    }

    $action = $Processor->getAction();
    $Model = $Processor->getModel();
    $Params = $Processor->getParams();
    $target = $Processor->getTarget();


    // all non read actions for status and items types are limited to admins
    if ($action !== 'read' &&
        ($Model instanceof Status || $Model instanceof ItemsTypes) &&
        !$App->Session->get('is_admin')
        ) {
        throw new IllegalActionException('Non admin user tried to edit status or items types.');
    }
    // only sysadmins can update the config
    if ($action === 'update' && $Model instanceof Config && !$App->Users->userData['is_sysadmin']) {
        throw new IllegalActionException('Non sysadmin user tried to update instance config.');
    }


    if ($action === 'create' && !$Model instanceof Config) {
        $res = $Model->create($Params);
        if ($Model instanceof ApiKeys) {
            $res = $Params->getKey();
        }
    } elseif ($action === 'read' && !$Model instanceof Config) {
        $res = $Model->read($Params);
    } elseif ($action === 'update') {
        // TODO should not exist, but it's here for now
        if ($Model instanceof ItemsTypes && ($target !== 'metadata')) {
            $res = $Model->updateAll($Params);
        } else {
            $res = $Model->update($Params);
        }
    } elseif ($action === 'destroy') {
        if ($Model instanceof Experiments) {
            $Teams = new Teams($App->Users);
            $teamConfigArr = $Teams->read(new ContentParams());
            if ((!$teamConfigArr['deletable_xp'] && !$App->Session->get('is_admin'))
                || $App->Config->configArr['deletable_xp'] === '0') {
                throw new ImproperActionException('You cannot delete experiments!');
            }
        }
        $res = $Model->destroy();
    } elseif ($action === 'destroystamppass' && ($Model instanceof Config || $Model instanceof Teams)) {
        $res = $Model->destroyStamppass();
    } elseif ($action === 'duplicate' && $Model instanceof AbstractEntity) {
        $res = $Model->duplicate();
    } elseif ($action === 'deduplicate' && $Model instanceof Tags) {
        $res = $Model->deduplicate();
    } elseif ($action === 'lock' && $Model instanceof AbstractEntity) {
        $res = $Model->toggleLock();
    }

    if ($Processor instanceof FormProcessor && !($Request->request->get('extraParam') === 'jsoneditor')) {
        $Response = new RedirectResponse('../../' . $Processor->Entity->page . '.php?mode=edit&id=' . $Processor->Entity->id);
        $Response->send();
        exit;
    }
    $Response->setData(array(
        'res' => true,
        'msg' => _('Saved'),
        'value' => $res,
    ));
} catch (Swift_TransportException $e) {
    // for swift error, don't display error to user as it might contain sensitive information
    // but log it and display general error. See #841
    $App->Log->error('', array('exception' => $e));
    $Response = new JsonResponse();
    $Response->setData(array(
        'res' => false,
        'msg' => _('Error sending email'),
    ));
} catch (ImproperActionException | UnauthorizedException | ResourceNotFoundException | PDOException $e) {
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
    $Response = new JsonResponse();
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
