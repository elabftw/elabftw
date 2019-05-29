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
use Elabftw\Models\ItemsTypes;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Deal with ajax requests sent from the admin page
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();
$Response->setData(array(
    'res' => true,
    'msg' => _('Saved'),
));

try {
    if (!$Session->get('is_admin')) {
        throw new IllegalActionException('Non admin user tried to access items types controller.');
    }

    $ItemsTypes = new ItemsTypes($App->Users);

    // CREATE ITEMS TYPES
    if ($Request->request->has('itemsTypesCreate')) {
        $ItemsTypes->create(
            $Request->request->get('name'),
            $Request->request->get('color'),
            (int) $Request->request->get('bookable'),
            $Request->request->get('template')
        );
    }

    // UPDATE ITEM TYPE
    if ($Request->request->has('itemsTypesUpdate')) {
        $ItemsTypes->update(
            (int) $Request->request->get('id'),
            $Request->request->get('name'),
            $Request->request->get('color'),
            (int) $Request->request->get('bookable'),
            $Request->request->get('template')
        );
    }

    // DESTROY ITEM TYPE
    if ($Request->request->has('itemsTypesDestroy')) {
        $ItemsTypes->destroy((int) $Request->request->get('id'));
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
