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
use Elabftw\Models\Users;

class UserCreated extends AbstractNotifications implements MailableInterface
{
    protected const PREF = 'notif_user_created';

    protected Notifications $category = Notifications::UserCreated;

    public function __construct(private int $userid, private string $team)
    {
        parent::__construct();
    }

    public function getEmail(): array
    {
        $user = new Users($this->userid);
        $body = sprintf(
            _('Hi. A new user registered an account on eLabFTW: %s (%s).'),
            $user->userData['fullname'],
            $user->userData['email'],
        );
        return array(
            'subject' => sprintf(_('New user added to team: %s'), $this->team),
            'body' => $body,
        );
    }

    protected function getBody(): array
    {
        return array(
            'userid' => $this->userid,
            'team' => $this->team,
        );
    }
}
