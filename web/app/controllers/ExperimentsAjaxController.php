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
use Elabftw\Models\Experiments;
use Elabftw\Models\Teams;
use Elabftw\Services\MakeTimestamp;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Experiments
 *
 */
require_once \dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();
$Response->setData(array(
    'res' => true,
    'msg' => _('Saved')
));

try {

    if ($App->Session->has('anon')) {
        throw new IllegalActionException('Anonymous user tried to access experiments controller.');
    }

    // CSRF
    $App->Csrf->validate();

    $Entity = new Experiments($App->Users);
    if ($Request->request->has('id')) {
        $Entity->setId((int) $Request->request->get('id'));
    }

    // CREATE STEP
    if ($Request->request->has('createStep')) {
        $Entity->Steps->create($Request->request->get('body'));
    }

    // FINISH STEP
    if ($Request->request->has('finishStep')) {
        $Entity->Steps->finish((int) $Request->request->get('stepId'));
    }

    // DESTROY STEP
    if ($Request->request->has('destroyStep')) {
        $Entity->Steps->destroy((int) $Request->request->get('stepId'));
    }

    // CREATE LINK
    if ($Request->request->has('createLink')) {
        $Entity->Links->create((int) $Request->request->get('linkId'));
    }

    // DESTROY LINK
    if ($Request->request->has('destroyLink')) {
        $Entity->Links->destroy((int) $Request->request->get('linkId'));
    }

    // GET LINK LIST
    if ($Request->query->has('term')) {
        $Response->setData($Entity->getLinkList($Request->query->get('term')));
    }

    // TIMESTAMP
    if ($Request->request->has('timestamp')) {
        $MakeTimestamp = new MakeTimestamp($App->Config, new Teams($App->Users), $Entity);
        $MakeTimestamp->timestamp();
    }

    // DESTROY
    if ($Request->request->has('destroy')) {
        $Teams = new Teams($App->Users);
        $teamConfigArr = $Teams->read();

        if (($teamConfigArr['deletable_xp'] == '0') && !$Session->get('is_admin')) {
            throw new ImproperActionException(_("You don't have the rights to delete this experiment."));
        }
        $Entity->destroy();
    }

    // DECODE ASN1 TOKEN
    if ($Request->request->has('asn1') && \is_readable(\dirname(__DIR__, 3) . "/uploads/" . $Request->request->get('asn1'))) {
        $MakeTimestamp = new MakeTimestamp($App->Config, new Teams($App->Users), $Entity);
        $Response->setData(array(
            'res' => true,
            'msg' => $MakeTimestamp->decodeAsn1($Request->request->get('asn1'))
        ));
    }

} catch (ImproperActionException $e) {
    $Response->setData(array(
        'res' => false,
        'msg' => $e->getMessage()
    ));

} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
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
    $Response->setData(array(
        'res' => false,
        'msg' => Tools::error()
    ));

} finally {
    $Response->send();
}
