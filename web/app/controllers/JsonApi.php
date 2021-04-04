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
use Elabftw\Interfaces\CreateParamsInterface;
use Elabftw\Interfaces\UpdateParamsInterface;
use Exception;
use PDOException;
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

    if ($Request->headers->get('Content-Type') !== 'application/json') {
        throw new IllegalActionException('This endpoint only accepts json requests.');
    }

    $Processor = new JsonProcessor($App->Users);
    $payload = $Processor->process($Request->getContent());
    $params = $Processor->getParams();

    if ($params instanceof CreateParamsInterface) {
        $res = $payload->model->create($params);
        $Response->setData(array(
            'res' => true,
            'msg' => _('Saved'),
            'value' => $res,
        ));
    } elseif ($params instanceof UpdateParamsInterface) {
        $res = $payload->model->update($params);
        $Response->setData(array(
            'res' => true,
            'msg' => _('Saved'),
            'value' => $res,
        ));
    }
    /*
    switch ($payload->action) {
        case 'update':
            break;

        default:
            throw new IllegalActionException('Bad action param on Ajax controller');
    }
     */
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
