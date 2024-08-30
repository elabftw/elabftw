<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\AuditEvent;

use Elabftw\Enums\AuditCategory;

class UserDeleted extends AbstractAuditEvent
{
    public function getBody(): string
    {
        return 'Account deleted';
    }

    public function getCategory(): AuditCategory
    {
        return AuditCategory::AccountDeleted;
    }
}
