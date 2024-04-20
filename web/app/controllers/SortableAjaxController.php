<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Enums\Orderable;

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\Experiments;
use Elabftw\Models\ExperimentsCategories;
use Elabftw\Models\ExperimentsStatus;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsStatus;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Teams;
use Elabftw\Models\Templates;
use Elabftw\Models\Todolist;
use Exception;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;

use function dirname;

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
    // decode JSON payload
    try {
        $reqBody = json_decode((string) $App->Request->getContent(), true, 5, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        throw new ImproperActionException('Error decoding JSON payload');
    }

    // extra fields position update
    if ($reqBody['table'] === 'extra_fields') {
        $OrderingParams = new ExtraFieldsOrderingParams($reqBody);
        $Entity = $OrderingParams->type->toInstance($App->Users, $OrderingParams->id);
        $Entity->updateExtraFieldsOrdering($OrderingParams);
        $Response->send();
        exit;
    }

    // rest of the tables
    $OrderingParams = new OrderingParams($reqBody);

    switch ($OrderingParams->table) {
        case Orderable::ExperimentsCategories:
            $Entity = new ExperimentsCategories(new Teams($App->Users));
            break;
        case Orderable::ItemsTypes:
            $Entity = new ItemsTypes($App->Users);
            break;
        case Orderable::ExperimentsStatus:
            $Entity = new ExperimentsStatus(new Teams($App->Users));
            break;
        case Orderable::ItemsStatus:
            $Entity = new ItemsStatus(new Teams($App->Users));
            break;
        case Orderable::ExperimentsSteps:
            $model = new Experiments($App->Users);
            $Entity = $model->Steps;
            break;
        case Orderable::ItemsSteps:
            $model = new Items($App->Users);
            $Entity = $model->Steps;
            break;
        case Orderable::Todolist:
            $Entity = new Todolist((int) $App->Users->userData['userid']);
            break;
        case Orderable::ExperimentsTemplates:
            $Entity = new Templates($App->Users);
            break;
        case Orderable::ExperimentsTemplatesSteps:
            $model = new Templates($App->Users);
            $Entity = $model->Steps;
            break;
        case Orderable::ItemsTypesSteps:
            $model = new ItemsTypes($App->Users);
            $Entity = $model->Steps;
            break;
        default:
            throw new IllegalActionException('Bad table for updateOrdering.');
    }
    $Entity->updateOrdering($OrderingParams);
} catch (ImproperActionException | UnauthorizedException $e) {
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
