<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\ImproperActionException;

/**
 * Find a team from an access_key
 */
class TeamFinder
{
    private string $ak;

    private Db $Db;

    public function __construct(private string $page, string $ak)
    {
        $this->ak = Check::accessKey($ak);
        $this->Db = Db::getConnection();
    }

    public function findTeam(): int
    {
        return match ($this->page) {
            EntityType::Experiments->getPage() => $this->searchIn(EntityType::Experiments),
            EntityType::Items->getPage() => $this->searchIn(EntityType::Items),
            default => throw new ImproperActionException('Wrong page!'),
        };
    }

    private function searchIn(EntityType $entityType): int
    {
        $sql = 'SELECT users2teams.teams_id FROM ' . $entityType->value . ' AS entity
            CROSS JOIN users2teams ON (users2teams.users_id = entity.userid)
            WHERE entity.access_key = :ak';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':ak', $this->ak);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }
}
