<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\AuditEvent;

use Elabftw\Enums\AuditCategory;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\RequestableAction;
use Override;

final class ActionRequested extends AbstractAuditEvent
{
    public function __construct(int $requesterUserid, int $targetUserid, private int $entityId, private EntityType $entityType, private RequestableAction $action)
    {
        parent::__construct($requesterUserid, $targetUserid);
    }

    #[Override]
    public function getBody(): string
    {
        return sprintf('An action has been requested: %s on %s (id: %d)', $this->action->toHuman(), $this->entityType->value, $this->entityId);
    }

    #[Override]
    public function getJsonBody(): string
    {
        $info = array_merge($this->getBaseInfo(), array(
            'entity_id' => $this->entityId,
            'entity_type' => $this->entityType->value,
            'action' => $this->action->value,
        ));
        return json_encode($info, JSON_THROW_ON_ERROR);
    }

    #[Override]
    public function getCategory(): AuditCategory
    {
        return AuditCategory::ActionRequested;
    }
}
