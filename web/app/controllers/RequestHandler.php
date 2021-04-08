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
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Status;
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
    // CSRF
    $App->Csrf->validate();

    if ($Request->headers->get('Content-Type') === 'application/json') {
        $Processor = new JsonProcessor($App->Users, $Request);
    } else {
        $Processor = new FormProcessor($App->Users, $Request);
    }

    $action = $Processor->getAction();
    $Model = $Processor->getModel();
    $Params = $Processor->getParams();


    // Status actions can only be accessed by admin level
    // TODO should probably not be here if we're going to use this to read too
    if ($Model instanceof Status && !$App->Session->get('is_admin')) {
        throw new IllegalActionException('Non admin user tried to access admin controller.');
    }
    if ($action === 'create') {
        $res = $Model->create($Params);
        if ($Model instanceof ApiKeys) {
            $res = $Params->getKey();
        }
    } elseif ($action === 'update') {
        // TODO should not exist, but it's here for now
        if ($Model instanceof ItemsTypes) {
            $res = $Model->updateAll($Params);
        } else {
            $res = $Model->update($Params);
        }
    } elseif ($action === 'destroy') {
        $res = $Model->destroy();
    } elseif ($action === 'duplicate' && $Model instanceof AbstractEntity) {
        $res = $Model->duplicate();
    } elseif ($action === 'lock' && $Model instanceof AbstractEntity) {
        $res = $Model->toggleLock();
    }

    if ($Processor instanceof FormProcessor) {
        $Response = new RedirectResponse('../../' . $Processor->Entity->page . '.php?mode=edit&id=' . $Processor->Entity->id);
        $Response->send();
        die;
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
    $Response->setData(array(
        'res' => false,
        'msg' => _('Error sending email'),
    ));
} catch (ImproperActionException | InvalidCsrfTokenException | UnauthorizedException | ResourceNotFoundException | PDOException $e) {
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
