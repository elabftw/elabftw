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
use Elabftw\Services\Filter;
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

    $Processor = new RequestProcessor($App->Users, $Request);
    $Model = $Processor->getModel();
    $action = $Processor->getAction();

    switch ($action) {
        case 'readForTinymce':
            // @phpstan-ignore-next-line
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
            // @phpstan-ignore-next-line
            $res = $Model->readAll();
            $Response->setData(array(
                'res' => true,
                'msg' => $res,
            ));
            break;

        case 'getList':
            $content = Filter::sanitize($Request->query->get('params')['name']);
            // @phpstan-ignore-next-line
            $Response->setData($Model->getList($content));
            break;

        case 'updateMember':
            // @phpstan-ignore-next-line
            $Model->updateMember(
                (int) $Request->request->get('params')['user'],
                (int) $Request->request->get('params')['group'],
                $Request->request->get('params')['how'],
            );
            break;

        case 'updateExtraField':
            // @phpstan-ignore-next-line
            $Model->updateExtraField(
                $Request->request->get('params')['field'],
                $Request->request->get('params')['value'],
            );
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
