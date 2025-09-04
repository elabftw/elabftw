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
        $App->demoMode,
    );

    // SEND TEST EMAIL
    if ($App->Request->request->has('testemailSend')) {
        $Email->testemailSend($App->Request->request->getString('email'));
    }

    $subject = $App->Request->request->getString('subject');
    $body = $App->Request->request->getString('body');
    // GET LIST OF RECIPIENTS' FULL NAMES
    if ($App->Request->request->has('listBookers')) {
        $result = $Email->getSurroundingBookers($App->Request->request->getInt('itemId'));
        $Response->setData(array('res' => true, 'fullnames' => $result['fullnames']));
    }
    // SEND MULTIPLE EMAILS (accepts a list of emails)
    if ($App->Request->request->has('notifyPastBookers')) {
        $replyTo = new Address($App->Users->userData['email'], $App->Users->userData['fullname']);
        $result = $Email->getSurroundingBookers($App->Request->request->getInt('itemId'));
        $emails = $result['emailAddresses'] ?? array();
        if (!$emails) {
            throw new ImproperActionException(_('No users/emails found for this resource\'s events.'));
        }
        foreach ($emails as $address) {
            try {
                $Email->sendEmail($address, $subject, $body, replyTo: $replyTo);
            } catch (ImproperActionException) {
                continue;
            }
        }
        $Response->setData(array(
            'res' => true,
            'msg' => sprintf(
                _('Email has been sent to %d %s who booked this item within ±4 months.'),
                count($emails),
                (count($emails) > 1 ? _('users') : _('user'))
            ),
        ));
    }
    // SEND MASS EMAIL (accepts a predefined list of users, admins, etc.)
    if ($App->Request->request->has('massEmail')) {
        $replyTo = new Address($App->Users->userData['email'], $App->Users->userData['fullname']);
        $Email->massEmail(
            EmailTarget::from($App->Request->request->getString('target')),
            null,
            $subject,
            $body,
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
} catch (IllegalActionException | ImproperActionException | UnauthorizedException $e) {
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
