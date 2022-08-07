<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ImproperActionException;
use PDO;

/**
 * Find a team from an elabid!
 */
class ElabidFinder
{
    private string $elabid;

    private Db $Db;

    public function __construct(private string $page, string $elabid)
    {
        // TODO Check::elabid
        $this->elabid = Filter::sanitize($elabid);
        $this->Db = Db::getConnection();
    }

    public function findTeam(): int
    {
        return match ($this->page) {
            '/experiments.php' => $this->searchIn('experiments'),
            '/database.php' => $this->searchIn('items'),
            default => throw new ImproperActionException('Wrong page!'),
        };
    }

    private function searchIn(string $entity): int
    {
        $sql = 'SELECT users2teams.teams_id FROM ' . $entity . ' AS entity
            CROSS JOIN users2teams ON (users2teams.users_id = entity.userid)
            WHERE entity.elabid = :elabid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':elabid', $this->elabid, PDO::PARAM_STR);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }
}
