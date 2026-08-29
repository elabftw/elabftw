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
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\MailableInterface;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Users\Users;
use Elabftw\Services\Email;
use Elabftw\Services\Filter;
use Elabftw\Services\TeamsHelper;
use Override;

use function _;
use function count;
use function sprintf;

final class EventDeleted extends AbstractNotifications implements MailableInterface, RestInterface
{
    protected const PREF = 'notif_event_deleted';

    protected Notifications $category = Notifications::EventDeleted;

    public function __construct(
        Users $targetUser,
        private array $event,
        private string $actor,
        private string $msg = '',
        private EmailTarget $target = EmailTarget::BookableItem,
    ) {
        parent::__construct($targetUser);
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
        if (!$this->canNotify()) {
            throw new ForbiddenException();
        }
        if (!empty($reqBody['msg'])) {
            $this->msg = Filter::body($reqBody['msg']);
        }
        $target = EmailTarget::tryFrom((string) ($reqBody['target'] ?? ''));
        if ($target === null) {
            throw new ImproperActionException('Invalid target for an event deletion notification.');
        }
        $this->target = $target;
        $targetId = $this->getTargetId($reqBody);
        $range = array(
            'direction' => $reqBody['range_direction'] ?? null,
            'value' => $reqBody['range_value'] ?? null,
            'unit' => $reqBody['range_unit'] ?? null,
        );
        $userids = Email::getIdsOfRecipients($this->target, $targetId, $range);
        foreach ($userids as $userid) {
            $recipient = new Users($userid);
            $Notif = new self($recipient, $this->event, $this->actor, $this->msg, $this->target);
            $Notif->create();
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

    #[Override]
    public function getEmail(): array
    {
        $info = _('A booked slot was deleted from the scheduler.');
        $url = Env::asUrl('SITE_URL') . '/scheduler.php?items[]=' . $this->event['item'];
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

    private function canNotify(): bool
    {
        $userid = $this->targetUser->getUserid();
        if ($this->event['userid'] === $userid) {
            return true;
        }
        return (new TeamsHelper($this->event['team']))->isAdminInTeam($userid);
    }

    private function getTargetId(array $reqBody): int
    {
        return match ($this->target) {
            EmailTarget::Team => $this->event['team'],
            EmailTarget::BookableItem,
            EmailTarget::BookableItemRange => $this->event['item'],
            EmailTarget::TeamGroup => $this->getTeamGroupTargetId((int) ($reqBody['targetid'] ?? 0)),
            default => throw new ImproperActionException('Invalid target for an event deletion notification.'),
        };
    }

    private function getTeamGroupTargetId(int $targetId): int
    {
        if ($targetId < 1) {
            throw new ImproperActionException('A team group target is required.');
        }
        $teamGroup = (new TeamGroups($this->targetUser, $targetId))->readOne();
        if ($teamGroup['team'] !== $this->event['team']) {
            throw new ImproperActionException('The team group does not belong to the event team.');
        }
        return $targetId;
    }
}
