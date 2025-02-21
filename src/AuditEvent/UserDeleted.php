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
use Override;

final class UserDeleted extends AbstractAuditEvent
{
    #[Override]
    public function getBody(): string
    {
        return 'Account deleted';
    }

    #[Override]
    public function getCategory(): AuditCategory
    {
        return AuditCategory::AccountDeleted;
    }
}
