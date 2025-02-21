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
use Elabftw\Models\Config;
use Override;

/**
 * When our account has been validated
 */
final class SelfIsValidated extends AbstractNotifications implements MailableInterface
{
    protected Notifications $category = Notifications::SelfIsValidated;

    // Note: here the actor fullname is directly fed to the instance, instead of fetching it from a new Users() like others.
    #[Override]
    public function getEmail(): array
    {
        $subject = _('Account validated');
        $url = Config::fromEnv('SITE_URL') . '/login.php';
        $body = sprintf(_('Hello. Your account on eLabFTW was validated by an admin. Follow this link to login: %s'), $url);
        return array('subject' => $subject, 'body' => $body);
    }
}
