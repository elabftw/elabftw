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
use Elabftw\Exceptions\IllegalActionException;
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
        if (!$this->requester->userData['is_sysadmin']) {
            throw new IllegalActionException('Non sysadmin user tried to access sysadmin controller.');
        }
        // CLEAR NOLOGIN
        if ($reqBody['clear-nologinusers']) {
            $this->Db->q('UPDATE users SET allow_untrusted = 1');
            return 0;
        }

        // CLEAR LOCKOUT DEVICES
        if ($reqBody['clear-lockoutdevices']) {
            $this->Db->q('DELETE FROM lockout_devices');
            return 0;
        }

        if ($reqBody['testemailSend']) {
            $this->email->testemailSend((string) $reqBody['email']);
            return 0;
        }
        if ($reqBody['massEmail']) {
            $this->email->massEmail(
                EmailTarget::from((string) $reqBody['target']),
                null,
                Filter::toPureString($reqBody['subject']),
                Filter::toPureString($reqBody['body']),
                new Address($this->requester->userData['email'], $this->requester->userData['fullname']),
                $this->emailSendGrouped,
            );
        }
        return 0;
    }
}
