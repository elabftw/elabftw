<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models\Notifications;

use Elabftw\Enums\Notifications;
use Elabftw\Interfaces\MailableInterface;
use Elabftw\Models\Config;
use Elabftw\Models\Users;

class CommentCreated extends AbstractNotifications implements MailableInterface
{
    protected const PREF = 'notif_comment_created';

    protected Notifications $category = Notifications::CommentCreated;

    public function __construct(private int $experimentId, private int $commenterId)
    {
        parent::__construct();
    }

    public function getEmail(): array
    {
        $commenter = new Users($this->commenterId);
        $url = sprintf('%s/experiments.php?mode=view&id=%d', Config::fromEnv('SITE_URL'), $this->experimentId);

        $body = sprintf(
            _('Hi. %s left a comment on your experiment. Have a look: %s'),
            $commenter->userData['fullname'],
            $url,
        );
        return array(
            'subject' => _('New comment posted'),
            'body' => $body,
        );
    }

    protected function getBody(): array
    {
        return array(
            'experiment_id' => $this->experimentId,
            'commenter_userid' => $this->commenterId,
        );
    }
}
