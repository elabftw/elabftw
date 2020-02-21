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
use Elabftw\Exceptions\InvalidCsrfTokenException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\Templates;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Deal with ajax requests. Regrouped to avoid code duplication.
 */
require_once \dirname(__DIR__) . '/init.inc.php';

// default response is happy, exception will be thrown on error and redefine the response accordingly
$Response = new JsonResponse();
$Response->setData(array(
    'res' => true,
    'msg' => _('Saved'),
));

try {
    // CSRF
    $App->Csrf->validate();

    // DUPLICATE/IMPORT TPL
    if ($Request->request->has('importTpl')) {
        $Templates = new Templates($App->Users, (int) $Request->request->get('id'));
        $Templates->duplicate();
    }

    // UPDATE COMMON TEMPLATE
    if ($Request->request->has('commonTplUpdate')) {
        if (!$App->Session->get('is_admin')) {
            throw new IllegalActionException('Non admin user tried to access admin controller.');
        }

        $Templates = new Templates($App->Users);
        $Templates->updateCommon($Request->request->get('commonTplUpdate'));
    }

    // DESTROY API KEY
    if ($Request->request->has('destroyApiKey')) {
        $ApiKeys = new ApiKeys($App->Users);
        $ApiKeys->destroy((int) $Request->request->get('id'));
    }

    // GET UPLOADED FILES
    if ($Request->query->has('getFiles')) {
        if ($Request->query->get('type') === 'experiments') {
            $Entity = new Experiments($App->Users, (int) $Request->query->get('id'));
        } else {
            $Entity = new Database($App->Users, (int) $Request->query->get('id'));
        }
        $Entity->canOrExplode('read');
        $uploads = $Entity->Uploads->readAll();
        $Response->setData($uploads);
    }

    // GET USER TEMPLATES
    if ($Request->query->has('getUserTpl')) {
        $Templates = new Templates($App->Users);
        $userTemplates = $Templates->readAll();
        $res = array();

        foreach ($userTemplates as $template) {
            $res[] = array('title' => $template['name'], 'description' => '', 'content' => $template['body']);
        }

        $Response->setData($res);
    }
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
} finally {
    $Response->send();
}
