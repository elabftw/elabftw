<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\AuditEvent;

use Elabftw\Enums\AuditCategory;
use Override;

/**
 * The requester is the impersonator, the target is the impersonated user
 */
final class ImpersonationStarted extends AbstractAuditEvent
{
    #[Override]
    public function getBody(): string
    {
        return 'Impersonation started';
    }

    #[Override]
    public function getCategory(): AuditCategory
    {
        return AuditCategory::ImpersonationStarted;
    }
}
