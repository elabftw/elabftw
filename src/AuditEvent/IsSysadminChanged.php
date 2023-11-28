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

class IsSysadminChanged extends AbstractAuditEvent
{
    public function __construct(private int $requesterUserid, private int $content, int $userid)
    {
        parent::__construct($userid);
    }

    public function getBody(): string
    {
        return sprintf(
            'User sysadmin rights was changed to %d by user with id %d',
            $this->content,
            $this->requesterUserid,
        );
    }

    public function getCategory(): int
    {
        return AuditCategory::AccountModified->value;
    }
}
