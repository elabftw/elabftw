<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Enums\EmailTarget;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\Users;
use Elabftw\Services\Email;
use Elabftw\Services\Filter;
use Override;
use Symfony\Component\Mime\Address;

/**
 * Instance level actions
 */
final class Instance extends AbstractRest
{
    public function __construct(private readonly Users $requester, private readonly Email $email, private bool $emailSendGrouped)
    {
        parent::__construct();
    }

    #[Override]
    public function getApiPath(): string
    {
        return 'api/v2/instance/';
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        if (!in_array($action, array(Action::EmailBookers, Action::EmailTeam), true)) {
            $this->requester->isSysadminOrExplode();
        }
        return match ($action) {
            Action::AllowUntrusted => $this->Db->qToRowCount('UPDATE users SET allow_untrusted = 1'),
            Action::ClearLockedOutDevices => $this->Db->qToRowCount('DELETE FROM lockout_devices'),
            Action::Test => $this->email->testemailSend((string) $reqBody['email']),
            Action::Email => $this->email->massEmail(
                EmailTarget::from((string) $reqBody['target']),
                null,
                Filter::toPureString($reqBody['subject']),
                Filter::toPureString($reqBody['body']),
                new Address($this->requester->userData['email'], $this->requester->userData['fullname']),
                $this->emailSendGrouped,
            ),
            Action::EmailBookers => $this->email->notifyBookers(
                $this->requester,
                $reqBody['subject'],
                $reqBody['body'],
                new Items($this->requester, (int) $reqBody['entity_id']),
            ),
            Action::EmailTeam => $this->emailTeam($reqBody),
            default => throw new ImproperActionException('Invalid action parameter sent.'),
        };
    }

    private function emailTeam(array $reqBody): int
    {
        $target = (string) $reqBody['target'];
        // default to team
        $targetId = $this->requester->userData['team'];
        $targetType = EmailTarget::Team;
        if (str_starts_with($target, 'teamgroup')) {
            $targetId = (int) explode('_', $target)[1];
            $targetType = EmailTarget::TeamGroup;
        }
        $replyTo = new Address($this->requester->userData['email'], $this->requester->userData['fullname']);
        return $this->email->massEmail(
            $targetType,
            $targetId,
            $reqBody['subject'],
            $reqBody['body'],
            $replyTo,
            $this->emailSendGrouped,
        );
    }
}
