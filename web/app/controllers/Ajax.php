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

    if ($Request->getMethod() === 'POST') {
        $action = $Request->request->get('action');
        $params = $Request->request->get('params') ?? array();
    } else {
        $action = $Request->query->get('action');
        $params = $Request->query->get('params') ?? array();
    }

    $Processor = new RequestProcessor($App->Users, $Request);
    $Model = $Processor->getModel();
    // TODO $Params = $Processor->getParams();
    $Params = new ParamsProcessor($params);

    switch ($action) {
        case 'readForTinymce':
            $templates = $Model->readForUser();
            $res = array();
            foreach ($templates as $template) {
                $res[] = array('title' => $template['title'], 'description' => '', 'content' => $template['body']);
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

        case 'updateItemType':
            $res = $Model->updateAll($Params);
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

        case 'updateExtraField':
            $Model->updateExtraField(
                $Request->request->get('params')['field'],
                $Request->request->get('params')['value'],
            );
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
