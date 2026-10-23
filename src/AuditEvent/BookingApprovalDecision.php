<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @author Moustapha / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\AuditEvent;

use Elabftw\Enums\AuditCategory;
use Override;

use function array_merge;
use function json_encode;
use function sprintf;

/** Audit the decision, including the actor, owner and affected recurrence. */
final class BookingApprovalDecision extends AbstractAuditEvent
{
    public function __construct(
        int $actorUserid,
        int $ownerUserid,
        private int $eventId,
        private string $decision,
        private ?string $recurrenceId = null,
    ) {
        parent::__construct($actorUserid, $ownerUserid);
    }

    #[Override]
    public function getBody(): string
    {
        return sprintf('Booking %d: %s', $this->eventId, $this->decision);
    }

    #[Override]
    public function getJsonBody(): string
    {
        return json_encode(array_merge($this->getBaseInfo(), array(
            'event_id' => $this->eventId,
            'decision' => $this->decision,
            'recurrence_id' => $this->recurrenceId,
        )), JSON_THROW_ON_ERROR);
    }

    #[Override]
    public function getCategory(): AuditCategory
    {
        return AuditCategory::BookingApprovalDecision;
    }
}
