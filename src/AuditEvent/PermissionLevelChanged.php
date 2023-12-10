<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\AuditEvent;

use Elabftw\Enums\Usergroup;

class PermissionLevelChanged extends AbstractUsers2TeamsModifiedEvent
{
    public function __construct(private int $requesterUserid, private int $group, int $userid, private int $teamid)
    {
        parent::__construct($requesterUserid, $userid);
    }

    public function getBody(): string
    {
        return sprintf(
            'User permission level was changed to %s in team %d',
            Usergroup::from($this->group)->toHuman(),
            $this->teamid,
        );
    }
}
