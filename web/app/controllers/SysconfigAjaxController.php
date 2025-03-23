<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Enums\EmailTarget;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\Idps;
use Elabftw\Services\Email;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;

use function dirname;

/**
 * Deal with ajax requests sent from the sysconfig page or full form from sysconfig.php
 */
require_once dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();
$Response->setData(array(
    'res' => true,
    'msg' => _('Saved'),
));

try {
    if (!$App->Users->userData['is_sysadmin']) {
        throw new IllegalActionException('Non sysadmin user tried to access sysadmin controller.');
    }

    $Email = new Email(
        new Mailer(Transport::fromDsn($App->Config->getDsn())),
        $App->Log,
        $App->Config->configArr['mail_from'],
    );

    // SEND TEST EMAIL
    if ($App->Request->request->has('testemailSend')) {
        $Email->testemailSend($App->Request->request->getString('email'));
    }

    // SEND MASS EMAIL
    if ($App->Request->request->has('massEmail')) {
        $replyTo = new Address($App->Users->userData['email'], $App->Users->userData['fullname']);
        $Email->massEmail(
            EmailTarget::from($App->Request->request->getString('target')),
            null,
            $App->Request->request->getString('subject'),
            $App->Request->request->getString('body'),
            $replyTo,
            (bool) $App->Config->configArr['email_send_grouped'],
        );
    }

    // DESTROY IDP
    if ($App->Request->request->has('idpsDestroy')) {
        $Idps = new Idps($App->Users, $App->Request->request->getInt('id'));
        $Idps->destroy();
    }

    // CLEAR NOLOGIN
    if ($App->Request->request->has('clear-nologinusers')) {
        // this is so simple and only used here it doesn't have its own function
        $Db = Db::getConnection();
        $Db->q('UPDATE users SET allow_untrusted = 1');
    }

    // CLEAR LOCKOUT DEVICES
    if ($App->Request->request->has('clear-lockoutdevices')) {
        // this is so simple and only used here it doesn't have its own function
        $Db = Db::getConnection();
        $Db->q('DELETE FROM lockout_devices');
    }
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $Response->setData(array(
        'res' => false,
        'msg' => Tools::error(true),
    ));
} catch (ImproperActionException | UnauthorizedException $e) {
    $Response->setData(array(
        'res' => false,
        'msg' => $e->getMessage(),
    ));
} catch (DatabaseErrorException $e) {
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
