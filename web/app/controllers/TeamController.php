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
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\Email;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;

use function dirname;

/**
 * Actions from team.php
 */
require_once dirname(__DIR__) . '/init.inc.php';

$Response = new RedirectResponse('/team.php?tab=4');
try {
    // NOT FOR ANON
    if ($App->Session->get('is_anon')) {
        throw new IllegalActionException('Anonymous user tried to send email to team');
    }

    // EMAIL TEAM
    if ($App->Request->request->has('emailUsers')) {
        $target = $App->Request->request->getString('target');
        // default to team
        $targetId = $App->Users->userData['team'];
        $targetType = EmailTarget::Team;
        if (str_starts_with($target, 'teamgroup')) {
            $targetId = (int) explode('_', $target)[1];
            $targetType = EmailTarget::TeamGroup;
        }
        $Email = new Email(
            new Mailer(Transport::fromDsn($App->Config->getDsn())),
            $App->Log,
            $App->Config->configArr['mail_from'],
        );
        $replyTo = new Address($App->Users->userData['email'], $App->Users->userData['fullname']);
        $sent = $Email->massEmail(
            $targetType,
            $targetId,
            $App->Request->request->getString('subject'),
            $App->Request->request->getString('body'),
            $replyTo,
            (bool) $App->Config->configArr['email_send_grouped'],
        );
        $App->Session->getFlashBag()->add('ok', sprintf(_('Email sent to %d users'), $sent));
    }
} catch (ImproperActionException $e) {
    // show message to user
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $App->Session->getFlashBag()->add('ko', Tools::error(true));
} catch (DatabaseErrorException | FilesystemErrorException $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $App->Session->getFlashBag()->add('ko', Tools::error());
} finally {
    $Response->send();
}
