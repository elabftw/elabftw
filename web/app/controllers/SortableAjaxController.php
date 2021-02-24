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
use Elabftw\Models\Status;
use Elabftw\Models\Templates;
use Elabftw\Models\Todolist;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Update ordering of various things
 */
require_once dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();
$Response->setData(array(
    'res' => true,
    'msg' => _('Saved'),
));

try {
    switch ($Request->request->get('table')) {
        case 'items_types':
            if (!$App->Session->get('is_admin')) {
                throw new IllegalActionException('Non admin user tried to access admin controller.');
            }
            $Entity = new ItemsTypes($App->Users);
            break;
        case 'status':
            if (!$App->Session->get('is_admin')) {
                throw new IllegalActionException('Non admin user tried to access admin controller.');
            }
            $Entity = new Status($App->Users);
            break;
        case 'experiments_steps':
            $model = new Experiments($App->Users);
            $Entity = $model->Steps;
            break;
        case 'items_steps':
            $model = new Database($App->Users);
            $Entity = $model->Steps;
            break;
        case 'todolist':
            $Entity = new Todolist($App->Users);
            break;
        case 'experiments_templates':
            $Entity = new Templates($App->Users);
            break;
        case 'experiments_templates_steps':
            $model = new Templates($App->Users);
            $Entity = $model->Steps;
            break;
        default:
            throw new IllegalActionException('Bad table for updateOrdering.');
    }
    $OrderingParams = new OrderingParams($Request->request->get('table'), $Request->request->get('ordering'));
    $Entity->updateOrdering($OrderingParams);
} catch (ImproperActionException | InvalidCsrfTokenException | UnauthorizedException $e) {
    $Response->setData(array(
        'res' => false,
        'msg' => $e->getMessage(),
    ));
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e->getMessage())));
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
    $Response->setData(array(
        'res' => false,
        'msg' => Tools::error(),
    ));
} finally {
    $Response->send();
}
