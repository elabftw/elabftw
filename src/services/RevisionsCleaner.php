<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Services;

use Elabftw\Elabftw\Db;
use Elabftw\Interfaces\CleanerInterface;

/**
 * Remove half of the stored revisions
 */
class RevisionsCleaner implements CleanerInterface
{
    /** @var Db $Db SQL Database */
    private $Db;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Remove every other row
     *
     * @param string $table
     * @return void
     */
    private function removeRows(string $table): void
    {
        // from https://stackoverflow.com/a/14261704
        $sql = "DELETE FROM " . $table . "_revisions
                WHERE id IN (
                    SELECT `id` FROM (
                        SELECT @row := @row + 1 AS 'rownum', t.`id`
                        FROM (SELECT @row :=0) r, (SELECT `id` FROM " . $table . "_revisions ORDER BY `id`) t
                    ) rev
                WHERE `rownum` % 2 = 0)";
        $req = $this->Db->prepare($sql);
        $req->execute();
    }

    /**
     * Do the purge
     *
     * @return int number of removed rows
     */
    public function cleanup()
    {
        $this->removeRows('experiments');
        $this->removeRows('items');
    }
}
