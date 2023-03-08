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

class EventDeleted extends AbstractNotifications implements MailableInterface
{
    protected const PREF = 'notif_event_deleted';

    protected Notifications $category = Notifications::EventDeleted;

    public function __construct(
        private array $event,
        private string $actor,
    ) {
        parent::__construct();
    }

    // Note: here the actor fullname is directly fed to the instance, instead of fetching it from a new Users() like others.
    public function getEmail(): array
    {
        $info = _('A booked slot was deleted from the scheduler.');
        $url = Config::fromEnv('SITE_URL') . '/team.php?item=' . $this->event['item'];
        $body = sprintf(_('Hi. %s (%s). See item: %s. It was booked from %s to %s.'), $info, $this->actor, $url, $this->event['start'], $this->event['end']);
        return array(
            'subject' => $info,
            'body' => $body,
        );
    }

    protected function getBody(): array
    {
        return array(
            'event' => $this->event,
            'actor' => $this->actor,
        );
    }
}
