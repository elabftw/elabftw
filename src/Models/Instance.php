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
        $this->requester->isSysadminOrExplode();
        match ($action) {
            Action::AllowUntrusted => $this->Db->q('UPDATE users SET allow_untrusted = 1'),
            Action::ClearLockedOutDevices => $this->Db->q('DELETE FROM lockout_devices'),
            Action::Test => $this->email->testemailSend((string) $reqBody['email']),
            Action::Email => $this->email->massEmail(
                EmailTarget::from((string) $reqBody['target']),
                null,
                Filter::toPureString($reqBody['subject']),
                Filter::toPureString($reqBody['body']),
                new Address($this->requester->userData['email'], $this->requester->userData['fullname']),
                $this->emailSendGrouped,
            ),
            default => throw new ImproperActionException('Invalid action parameter sent.'),
        };
        return 0;
    }
}
