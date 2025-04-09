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
use Elabftw\Interfaces\AuditEventInterface;
use Override;

abstract class AbstractAuditEvent implements AuditEventInterface
{
    public function __construct(private int $requesterUserid = 0, private int $targetUserid = 0) {}

    #[Override]
    public function getTargetUserid(): int
    {
        return $this->targetUserid;
    }

    #[Override]
    public function getRequesterUserid(): int
    {
        return $this->requesterUserid;
    }

    #[Override]
    abstract public function getBody(): string;

    #[Override]
    public function getJsonBody(): string
    {
        return json_encode($this->getBaseInfo(), JSON_THROW_ON_ERROR);
    }

    #[Override]
    abstract public function getCategory(): AuditCategory;

    protected function getBaseInfo(): array
    {
        return array(
            'category_value' => $this->getCategory()->value,
            'category_name' => $this->getCategory()->name,
            'message' => $this->getBody(),
            'requester_userid' => $this->getRequesterUserid(),
            'target_userid' => $this->getTargetUserid(),
        );
    }
}
