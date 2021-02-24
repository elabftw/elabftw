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

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidCsrfTokenException;
use Elabftw\Exceptions\UnauthorizedException;
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
    'msg' => _('Saved'),
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

    // TIMESTAMP
    if ($Request->request->has('timestamp')) {
        $MakeTimestamp = new MakeTimestamp($App->Config, new Teams($App->Users), $Entity);
        $MakeTimestamp->timestamp();
    }

    // DESTROY
    if ($Request->request->has('destroy')) {
        $Teams = new Teams($App->Users);
        $teamConfigArr = $Teams->read();

        if (($teamConfigArr['deletable_xp'] == '0') && !$App->Session->get('is_admin')) {
            throw new ImproperActionException(_("You don't have the rights to delete this experiment."));
        }
        $Entity->destroy();
    }

    // DECODE ASN1 TOKEN
    if ($Request->request->has('asn1') && \is_readable(\dirname(__DIR__, 3) . '/uploads/' . $Request->request->get('asn1'))) {
        $MakeTimestamp = new MakeTimestamp($App->Config, new Teams($App->Users), $Entity);
        $Response->setData(array(
            'res' => true,
            'msg' => $MakeTimestamp->decodeAsn1($Request->request->get('asn1')),
        ));
    }
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
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e)));
    $Response->setData(array(
        'res' => false,
        'msg' => Tools::error(),
    ));
} finally {
    $Response->send();
}
