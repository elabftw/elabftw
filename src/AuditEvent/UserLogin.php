<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\AuditEvent;

use Elabftw\Enums\AuditCategory;

class UserLogin extends AbstractAuditEvent
{
    public function getBody(): string
    {
        return 'User logged in';
    }

    public function getCategory(): AuditCategory
    {
        return AuditCategory::Login;
    }
}
