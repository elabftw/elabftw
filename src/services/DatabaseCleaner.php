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
use Elabftw\Interfaces\CleanerInterface;

/**
 * Make sure the database is consistent with no leftover things
 */
class DatabaseCleaner implements CleanerInterface
{
    private Db $Db;

    public function __construct()
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Check all the things
     */
    public function cleanup(): int
    {
        $this->findOrphans('experiments_templates', 'teams', 'team');
        $this->findOrphans('experiments', 'users', 'userid', 'userid');
        $this->findOrphans('experiments_comments', 'experiments', 'item_id');
        $this->findOrphans('experiments_comments', 'users', 'userid', 'userid');
        $this->findOrphans('experiments_links', 'experiments', 'item_id');
        $this->findOrphans('experiments_links', 'items', 'link_id');
        $this->findOrphans('experiments_revisions', 'experiments', 'item_id');
        $this->findOrphans('experiments_revisions', 'users', 'userid', 'userid');
        $this->findOrphans('items_revisions', 'items', 'item_id');
        $this->findOrphans('items_revisions', 'users', 'userid', 'userid');
        $this->findOrphans('experiments_steps', 'experiments', 'item_id');
        $this->findOrphans('items', 'teams', 'team');
        $this->findOrphans('items_comments', 'items', 'item_id');
        $this->findOrphans('items_comments', 'users', 'userid', 'userid');
        $this->findOrphans('items_types', 'teams', 'team');
        $this->findOrphans('status', 'teams', 'team');
        $this->findOrphans('tags', 'teams', 'team');
        $this->findOrphans('team_events', 'teams', 'team');
        $this->findOrphans('team_events', 'users', 'userid', 'userid');
        $this->findOrphans('team_groups', 'teams', 'team');
        $this->findOrphans('todolist', 'users', 'userid', 'userid');

        return 0;
    }

    /**
     * Find orphaned rows
     *
     * @param string $table the table to clean
     * @param string $foreignTable the table where we check if the id exists
     * @param string $foreignKey the name of the FK in the $table
     * @param string $foreignId is id everywhere except userid in users table
     */
    private function findOrphans(string $table, string $foreignTable, string $foreignKey, string $foreignId = 'id'): void
    {
        $tableId = 'id';
        if ($table === 'users') {
            $tableId = 'userid';
        }

        $sql = 'SELECT ' . $table . '.' . $tableId . '
            FROM ' . $table . '
            LEFT JOIN ' . $foreignTable . ' ON (' . $table . '.' . $foreignKey . ' = ' . $foreignTable . '.' . $foreignId . ')
            WHERE ' . $foreignTable . '.' . $foreignId . ' IS NULL';
        $req = $this->Db->prepare($sql);
        $req->execute();
        $res = $req->fetchAll();
        if (!empty($res)) {
            echo 'Found ' . (string) \count($res) . ' rows to delete in ' . $table . "\n";
            $this->deleteFrom($table, $res);
        }
    }

    /**
     * Delete rows from a table
     *
     * @param string $table the mysql table to act upon
     * @param array<array-key, mixed> $results the results from the search
     */
    private function deleteFrom(string $table, array $results): void
    {
        $sql = 'DELETE FROM ' . $table . ' WHERE id = :id';
        $req = $this->Db->prepare($sql);
        foreach ($results as $orphan) {
            $req->bindParam(':id', $orphan['id']);
            $req->execute();
        }
    }
}
