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

use function array_find;

/**
 * Find a team from an access_key
 */
final class TeamFinder
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
        $entityType = array_find(
            EntityType::cases(),
            fn(EntityType $type): bool => $type->toPage() === $this->page,
        );

        if ($entityType === null) {
            throw new ImproperActionException('Wrong page!');
        }

        return $this->searchIn($entityType);
    }

    private function searchIn(EntityType $entityType): int
    {
        $sql = 'SELECT team FROM '
            . $entityType->value
            . ' WHERE access_key = :ak';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':ak', $this->ak);
        $this->Db->execute($req);

        return (int) $req->fetchColumn();
    }
}
