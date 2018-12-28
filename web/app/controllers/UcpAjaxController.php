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
use Elabftw\Models\Templates;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Deal with ajax requests sent from the user control panel
 */
require_once \dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();
$Response->setData(array(
    'res' => true,
    'msg' => _('Saved')
));

try {
    // TAB 3 : EXPERIMENTS TEMPLATES

    // DUPLICATE/IMPORT TPL
    if ($Request->request->has('import_tpl')) {
        $Templates = new Templates($App->Users, (int) $Request->request->get('id'));
        $Templates->duplicate();
    }

    // UPDATE ORDERING
    if ($Request->request->has('updateOrdering')) {
        if ($Request->request->get('table') === 'experiments_templates') {
            // remove the create new entry
            unset($Request->request->get('ordering')[0]);
            $Entity = new Templates($App->Users);
        }

        $Entity->updateOrdering($Request->request->all());
    }

} catch (ImproperActionException $e) {
    $Response->setData(array(
        'res' => false,
        'msg' => $e->getMessage()
    ));

} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e->getMessage())));
    $Response->setData(array(
        'res' => false,
        'msg' => Tools::error(true)
    ));

} catch (DatabaseErrorException | FilesystemErrorException $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $Response->setData(array(
        'res' => false,
        'msg' => $e->getMessage()
    ));

} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e)));

} finally {
    $Response->send();
}
