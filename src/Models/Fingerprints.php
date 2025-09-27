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
use InvalidArgumentException;
use PDO;

use function array_sum;
use function count;

/**
 * Fingerprints for compounds
 */
final class Fingerprints
{
    protected Db $Db;

    public function __construct(private ?int $compound = null)
    {
        $this->Db = Db::getConnection();
    }

    public function create(array $fp): int
    {
        $this->assertCompoundSet();
        // it's fine to send us an empty fp, but we won't record it
        if (array_sum($fp) === 0) {
            return 0;
        }
        if (count($fp) !== 32) {
            throw new InvalidArgumentException('Fingerprint payload must contain 32 integers');
        }
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
        $this->Db->execute($req);
        return $this->Db->lastInsertId();
    }

    public function upsert(array $fp): int
    {
        // destroy first to avoid PK clash
        $this->destroy();
        return $this->create($fp);
    }

    public function destroy(): bool
    {
        $this->assertCompoundSet();
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

    public function getSmilesMissingFp(bool $all = false): array
    {
        $sql = 'SELECT compounds.id, compounds.smiles FROM compounds
            LEFT JOIN compounds_fingerprints ON (compounds_fingerprints.id = compounds.id)';
        if (!$all) {
            $sql .= ' WHERE compounds_fingerprints.id IS NULL';
        }
        $req = $this->Db->prepare($sql);
        $req->execute();
        return $req->fetchAll();
    }

    private function assertCompoundSet(): void
    {
        if ($this->compound === null) {
            throw new InvalidArgumentException('Compound id is required for this operation');
        }
    }
}
