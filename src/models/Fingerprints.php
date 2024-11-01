<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use PDO;

/**
 * Fingerprints for compounds
 */
class Fingerprints
{
    protected Db $Db;

    public function __construct(private int $compound)
    {
        $this->Db = Db::getConnection();
    }

    public function create(array $fp): int
    {
        $sql = 'INSERT INTO compounds_fingerprints (id, ';
        for ($i = 0; $i < 32; $i++) {
            $sql .= sprintf('fp%d,', $i);
        }
        $sql = rtrim($sql, ',');
        $sql .= ') VALUES(:id, ';
        for ($i = 0; $i < 32; $i++) {
            $sql .= sprintf(':fp%d,', $i);
        }
        $sql = rtrim($sql, ',');
        $sql .= ')';

        $req = $this->Db->prepare($sql);
        for ($i = 0; $i < 32; $i++) {
            $req->bindParam(":fp$i", $fp[$i], PDO::PARAM_INT);
        }
        $req->bindParam(':id', $this->compound, PDO::PARAM_INT);
        $req->execute();
        return $this->Db->lastInsertId();
    }

    public function destroy(): bool
    {
        $sql = 'DELETE FROM compounds_fingerprints WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->compound, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    public function search(array $fp): array
    {
        $sql = 'SELECT entity_id, entity_type FROM compounds WHERE 1=1';
        foreach ($fp as $key => $value) {
            if ($value == 0) {
                continue;
            }
            $sql .= sprintf(' AND fp%d & %d = %d', $key, $value, $value);
        }
        $req = $this->Db->prepare($sql . ' LIMIT 2');
        $req->execute();
        return $req->fetchAll();
    }
}
