<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\AuditEvent;

use Elabftw\Enums\AuditCategory;

class PasswordResetRequested extends AbstractAuditEvent
{
    public function __construct(private string $email)
    {
    }

    public function getBody(): string
    {
        return sprintf('Password reset was requested for account associated with: %s', $this->email);
    }

    public function getCategory(): int
    {
        return AuditCategory::PasswordResetRequested->value;
    }
}
