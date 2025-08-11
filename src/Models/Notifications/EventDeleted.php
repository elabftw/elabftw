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

use Elabftw\Elabftw\Env;
use Elabftw\Enums\Action;
use Elabftw\Enums\EmailTarget;
use Elabftw\Enums\Notifications;
use Elabftw\Interfaces\MailableInterface;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Services\Email;
use Elabftw\Services\Filter;
use Override;

final class EventDeleted extends AbstractNotifications implements MailableInterface, RestInterface
{
    protected const PREF = 'notif_event_deleted';

    protected Notifications $category = Notifications::EventDeleted;

    public function __construct(
        private array $event,
        private string $actor,
        private string $msg = '',
        private EmailTarget $target = EmailTarget::BookableItem,
    ) {
        parent::__construct();
    }

    #[Override]
    public function readOne(): array
    {
        return array();
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        return array();
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        if (!empty($reqBody['msg'])) {
            $this->msg = Filter::body($reqBody['msg']);
        }
        // target can be bookable_item, team or teamgroup
        $this->target = EmailTarget::from($reqBody['target']);
        $userids = Email::getIdsOfRecipients($this->target, $reqBody['targetid']);
        foreach ($userids as $userid) {
            $this->create($userid);
        }
        return count($userids);
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        return array();

    }

    #[Override]
    public function getApiPath(): string
    {
        return 'api/v2/nah';
    }

    #[Override]
    public function destroy(): bool
    {
        return false;
    }

    // Note: here the actor fullname is directly fed to the instance, instead of fetching it from a new Users() like others.
    #[Override]
    public function getEmail(): array
    {
        $info = _('A booked slot was deleted from the scheduler.');
        $url = Env::asUrl('SITE_URL') . '/team.php?item=' . $this->event['item'];
        $body = sprintf(_('Hi. %s (%s). See item: %s. It was booked from %s to %s.'), $info, $this->actor, $url, $this->event['start'], $this->event['end']);
        if (!empty($this->msg)) {
            $body .= "\n\n" . _('Message:') . "\n" . $this->msg;
        }
        return array(
            'subject' => $info,
            'body' => $body,
            'target' => $this->target,
        );
    }

    #[Override]
    protected function getBody(): array
    {
        return array(
            'event' => $this->event,
            'actor' => $this->actor,
            'msg' => $this->msg,
            'target' => $this->target,
        );
    }
}
