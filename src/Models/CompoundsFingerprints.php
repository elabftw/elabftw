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

/**
 * Fingerprints of the compounds, see ReactionComponentFingerprints for the ones of reaction components
 */
final class CompoundsFingerprints
{
    protected Db $Db;

    private FingerprintTable $Table;

    public function __construct(private ?int $compound = null)
    {
        $this->Db = Db::getConnection();
        $this->Table = FingerprintTable::forCompounds();
    }

    public function create(array $fp): int
    {
        $compound = $this->getCompoundOrExplode();
        // it's fine to send us an empty fp, but we won't record it
        if (array_sum($fp) === 0) {
            return 0;
        }
        $this->Table->insert($compound, $fp);
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
        $compound = $this->getCompoundOrExplode();
        $sql = 'DELETE FROM compounds_fingerprints WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $compound, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    public function search(array $fp): array
    {
        $sql = 'SELECT entity_id, entity_type FROM compounds WHERE 1=1' . FingerprintTable::containsSql($fp);
        $req = $this->Db->prepare($sql . ' LIMIT 2');
        $this->Db->execute($req);
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
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    private function getCompoundOrExplode(): int
    {
        return $this->compound ?? throw new InvalidArgumentException('Compound id is required for this operation');
    }
}
