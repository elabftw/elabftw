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
use Exception;
use function explode;
use Swift_TransportException;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for the experiments comments
 *
 */
require_once dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();
$Response->setData(array(
    'res' => true,
    'msg' => _('Saved'),
));

try {
    if ($App->Session->has('anon')) {
        throw new IllegalActionException('Anonymous user tried to access database controller.');
    }

    // CSRF
    $App->Csrf->validate();

    if ($Request->request->get('type') === 'experiments') {
        $Entity = new Experiments($App->Users);
    } else {
        $Entity = new Database($App->Users);
    }

    // CREATE
    if ($Request->request->has('create')) {
        $Entity->setId((int) $Request->request->get('id'));
        $commentId = $Entity->Comments->create($Request->request->get('comment'));
        $Response->setData(array(
            'res' => true,
            'msg' => _('Saved'),
            'id' => $commentId,
        ));
    }

    // UPDATE
    if ($Request->request->has('update')) {
        // get the id from the sent value (comment_42)
        $exploded = explode('_', $Request->request->get('id'));
        $id = (int) $exploded[1];
        $res = $Entity->Comments->update($Request->request->get('update'), $id);
        $Response->setData(array(
            'res' => true,
            'msg' => _('Saved'),
            'update' => $res,
        ));
    }

    // DESTROY
    if ($Request->request->has('destroy')) {
        $Entity->Comments->destroy((int) $Request->request->get('id'));
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
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
} finally {
    $Response->send();
}
