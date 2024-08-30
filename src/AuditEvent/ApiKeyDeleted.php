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

class ApiKeyDeleted extends AbstractAuditEvent
{
    public function getBody(): string
    {
        return 'An API key was deleted';
    }

    public function getCategory(): AuditCategory
    {
        return AuditCategory::ApiKeyDeleted;
    }
}
