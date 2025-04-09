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
use Override;

class UserAttributeChanged extends AbstractAuditEvent
{
    public function __construct(
        int $requesterUserid,
        int $targetUserid,
        private string $attribute,
        private string $old,
        private string $new,
    ) {
        parent::__construct($requesterUserid, $targetUserid);
    }

    #[Override]
    public function getBody(): string
    {
        return sprintf(
            'User attribute %s was changed from %s to %s',
            $this->attribute,
            empty($this->old) ? 'nothing' : $this->old,
            $this->new,
        );
    }

    #[Override]
    public function getCategory(): AuditCategory
    {
        return AuditCategory::AccountModified;
    }
}
