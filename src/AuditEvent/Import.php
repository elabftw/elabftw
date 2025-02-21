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

final class Import extends AbstractAuditEvent
{
    public function __construct(int $requesterUserid, private int $count)
    {
        parent::__construct($requesterUserid, 0);
    }

    #[Override]
    public function getBody(): string
    {
        return sprintf('User imported %d entries', $this->count);
    }

    #[Override]
    public function getCategory(): AuditCategory
    {
        return AuditCategory::Import;
    }
}
