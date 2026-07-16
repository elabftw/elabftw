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
use Elabftw\Enums\EntityType;
use Override;

use function array_merge;
use function json_encode;
use function sprintf;

final class ResourceDeleted extends AbstractAuditEvent
{
    public function __construct(int $requesterUserid, private int $entityId, private EntityType $entityType, private string $reason)
    {
        parent::__construct($requesterUserid, 0);
    }

    #[Override]
    public function getBody(): string
    {
        return sprintf('Resource deleted. Reason: %s', $this->reason);
    }

    #[Override]
    public function getJsonBody(): string
    {
        $info = array_merge($this->getBaseInfo(), array(
            'entity_id' => $this->entityId,
            'entity_type' => $this->entityType->value,
            'reason' => $this->reason,
        ));
        return json_encode($info, JSON_THROW_ON_ERROR);
    }

    #[Override]
    public function getCategory(): AuditCategory
    {
        return AuditCategory::ResourceDeleted;
    }
}
