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

class PasswordChanged extends AbstractAuditEvent
{
    public function getBody(): string
    {
        return 'Password was changed';
    }

    public function getCategory(): int
    {
        return AuditCategory::PasswordChanged->value;
    }
}
