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

use function sprintf;

final class Import extends AbstractAuditEvent
{
    public function __construct(private readonly int $requesterUserid, private readonly int $targetUserid, private readonly int $importedCount)
    {
        parent::__construct($requesterUserid, $targetUserid);
    }

    #[Override]
    public function getBody(): string
    {
        return sprintf('User imported %d entries', $this->importedCount);
    }

    #[Override]
    public function getCategory(): AuditCategory
    {
        return AuditCategory::Import;
    }
}
