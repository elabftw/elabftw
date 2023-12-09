<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\AuditEvent;

use Elabftw\Enums\AuditCategory;

class UserAttributeChanged extends AbstractAuditEvent
{
    public function __construct(
        private int $requesterUserid,
        private string $target,
        private string $old,
        private string $new,
        int $targetUserid,
    ) {
        parent::__construct($requesterUserid, $targetUserid);
    }

    public function getBody(): string
    {
        return sprintf(
            'User attribute %s was changed from %s to %s',
            $this->target,
            $this->old,
            $this->new,
        );
    }

    public function getCategory(): int
    {
        return AuditCategory::AccountModified->value;
    }
}
