<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\State;
use Elabftw\Models\Users;
use PDO;

use function array_column;

/**
 * When we want to check for something.
 */
class UsersHelper
{
    private Db $Db;

    public function __construct(private int $userid)
    {
        $this->Db = Db::getConnection();
    }

    public function cannotBeDeleted(): bool
    {
        return $this->hasExperiments() || $this->hasItems() || $this->isSysadmin() || $this->hasComments() || $this->hasTemplates() || $this->hasUploads();
    }

    /**
     * Count all the experiments owned by a user
     */
    public function countExperiments(): int
    {
        $sql = 'SELECT COUNT(id) FROM experiments WHERE userid = :userid AND state = :state';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    /**
     * Count all the items owned by a user
     */
    public function countItems(): int
    {
        $sql = 'SELECT COUNT(id) FROM items WHERE userid = :userid AND state = :state';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    /**
     * Count all the timestamped experiments owned by a user
     */
    public function countTimestampedExperiments(): int
    {
        $sql = 'SELECT COUNT(id) FROM experiments WHERE userid = :userid AND timestamped = 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    /**
     * Get the team id where the user belong
     * @return array{array{id: int, name: string, is_owner: int, usergroup: int}} | array
     */
    public function getTeamsFromUserid(): array
    {
        $sql = 'SELECT DISTINCT teams.id, teams.name, users2teams.groups_id AS usergroup, users2teams.is_owner FROM teams
            CROSS JOIN users2teams ON (users2teams.users_id = :userid AND users2teams.teams_id = teams.id)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    /**
     * Get teams id from a userid
     */
    public function getTeamsIdFromUserid(): array
    {
        return array_column($this->getTeamsFromUserid(), 'id');
    }

    /**
     * Get teams name from a userid
     */
    public function getTeamsNameFromUserid(): array
    {
        return array_column($this->getTeamsFromUserid(), 'name');
    }

    private function countComments(): int
    {
        $sql = 'SELECT SUM(comment_count) AS total_comment_count
            FROM (
                SELECT COUNT(*) AS comment_count
                FROM experiments_comments
                WHERE userid = :userid
                UNION ALL
                SELECT COUNT(*) AS comment_count
                FROM items_comments
                WHERE userid = :userid
            ) AS subquery;';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    private function countTable(string $table): int
    {
        $sql = sprintf('SELECT COUNT(id) FROM %s WHERE userid = :userid', $table);
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    private function hasExperiments(): bool
    {
        return $this->countExperiments() > 0;
    }

    private function hasItems(): bool
    {
        return $this->countItems() > 0;
    }

    private function hasComments(): bool
    {
        return $this->countComments() > 0;
    }

    private function hasTemplates(): bool
    {
        return $this->countTable('experiments_templates') > 0;
    }

    private function hasUploads(): bool
    {
        return $this->countTable('uploads') > 0;
    }

    private function isSysadmin(): bool
    {
        $Users = new Users($this->userid);
        return $Users->userData['is_sysadmin'] === 1;
    }
}
