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

use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidCsrfTokenException;
use Elabftw\Models\Templates;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * In TinyMCE, in Insert > Insert template... it will fetch this URL without any parameter. And no parameters can be configured.
 * So it has a special page just for that.
 */
require_once \dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();
try {
    // GET USER TEMPLATES
    $Templates = new Templates($App->Users);
    $userTemplates = $Templates->readAll();
    $res = array();

    foreach ($userTemplates as $template) {
        $res[] = array('title' => $template['name'], 'description' => '', 'content' => $template['body']);
    }

    $Response->setData($res);
} catch (ImproperActionException | InvalidCsrfTokenException $e) {
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
} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid') ?? 'anon'), array('Exception' => $e)));
    $Response->setData(array(
        'res' => false,
        'msg' => Tools::error(),
    ));
} finally {
    $Response->send();
}
