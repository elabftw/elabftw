<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use function dirname;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Factories\ProcessorFactory;
use Elabftw\Models\Links;
use Elabftw\Models\Tags;
use Exception;
use PDOException;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * This is the main endpoint for requests. It can deal with json requests or classical forms.
 */
require_once dirname(__DIR__) . '/init.inc.php';

// the default response is a failed json response
$Response = new JsonResponse();
$Response->setData(array(
    'res' => false,
    'msg' => Tools::error(),
));
// this is the result of the processed action
$res = '';

try {
    $Processor = (new ProcessorFactory)->getProcessor($App->Users, $Request);
    $action = $Processor->getAction();
    $Model = $Processor->getModel();
    $Params = $Processor->getParams();
    $target = $Processor->getTarget();


    if ($action === 'create') {
        $res = $Model->create($Params);
    } elseif ($action === 'read') {
        $res = $Model->read($Params);
    } elseif ($action === 'update') {
        $res = $Model->update($Params);
    } elseif ($action === 'destroy') {
        $res = $Model->destroy();
    } elseif ($action === 'deduplicate' && $Model instanceof Tags) {
        $res = $Model->deduplicate();
    } elseif ($action === 'importlinks' && $Model instanceof Links) {
        $res = $Model->import();
    }

    // the value param can hold a value used in the page
    $Response->setData(array(
        'res' => true,
        'msg' => _('Saved'),
        'value' => $res,
    ));
} catch (ImproperActionException | UnauthorizedException | ResourceNotFoundException | PDOException $e) {
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
    $Response = new JsonResponse();
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
