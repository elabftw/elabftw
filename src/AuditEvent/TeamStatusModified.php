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

use Elabftw\Enums\BinaryValue;
use Elabftw\Enums\Users2TeamsTargets;
use Override;

final class TeamStatusModified extends AbstractUsers2TeamsModifiedEvent
{
    public function __construct(private int $teamid, private Users2TeamsTargets $target, private BinaryValue $content, int $requester, int $userid)
    {
        parent::__construct($requester, $userid);
    }

    #[Override]
    public function getBody(): string
    {
        return sprintf(
            'User attribute %s in team %d was changed from %s to %s',
            $this->target->value,
            $this->teamid,
            (string) $this->content->inverse()->value,
            (string) $this->content->value,
        );
    }
}
