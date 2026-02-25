<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models\Notifications;

use Elabftw\Enums\Notifications;
use Elabftw\Interfaces\MailableInterface;
use Override;

/**
 * Send an email to a new user to notify that admin validation is required.
 * This exists because experience shows that users don't read the notification and expect
 * their account to work right away.
 */
final class SelfNeedValidation extends AbstractNotifications implements MailableInterface
{
    protected Notifications $category = Notifications::SelfNeedValidation;

    // Note: here the actor fullname is directly fed to the instance, instead of fetching it from a new Users() like others.
    #[Override]
    public function getEmail(): array
    {
        $subject = _('Your account has been created');
        $body = _('Hi. Your account has been created but it is currently inactive (you cannot log in). The team admin has been notified and will validate your account. You will receive an email when it is done.');
        return array('subject' => $subject, 'body' => $body);
    }
}
