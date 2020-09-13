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
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Status;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Ajax requests from admin page
 *
 */
require_once dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();
$Response->setData(array(
    'res' => true,
    'msg' => _('Saved'),
));

try {
    if (!$App->Session->get('is_admin')) {
        throw new IllegalActionException('Non admin user tried to access admin controller.');
    }

    // CSRF
    $App->Csrf->validate();

    // GET USER LIST
    if ($Request->query->has('term') && !$Request->query->has('mention')) {
        $Response->setData($App->Users->lookFor($Request->query->get('term')));
    }

    // POST REQUESTS
    if ($Request->getMethod() === 'POST') {
        switch ($Request->request->get('what')) {
            case 'status':
                $Model = new Status($App->Users);
                break;
            case 'itemsTypes':
                $Model = new ItemsTypes($App->Users);
                break;
            default:
                throw new IllegalActionException('Bad what param on AdminAjaxController');
        }

        $Params = new ParamsProcessor($Request->request->get('params'));

        switch ($Request->request->get('action')) {
            case 'create':
                $Model->create($Params);
                break;
            case 'update':
                $Model->update($Params);
                break;
            case 'destroy':
                $Model->destroy($Params->id);
                break;
            default:
                throw new IllegalActionException('Bad action param on AdminAjaxController');
        }
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
