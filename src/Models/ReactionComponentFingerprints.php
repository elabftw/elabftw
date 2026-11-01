<?php

/**
 * @copyright 2026 Nicolas CARPi
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use InvalidArgumentException;
use PDO;

use function count;
use function implode;
use function sprintf;

/**
 * Fingerprints of reaction components, for substructure search. Same layout as compounds_fingerprints.
 */
final class ReactionComponentFingerprints
{
    private Db $Db;

    public function __construct()
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Components with a SMILES, by default only the ones without a fingerprint yet
     */
    public function getComponents(bool $all = false): array
    {
        $sql = "SELECT c.id, c.smiles FROM reaction_components AS c
            LEFT JOIN reaction_component_fingerprints AS f ON (f.component_id = c.id)
            WHERE c.smiles != ''";
        if (!$all) {
            $sql .= ' AND f.component_id IS NULL';
        }
        $req = $this->Db->prepare($sql . ' ORDER BY c.id');
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    public function upsert(int $componentId, array $fingerprint): void
    {
        if (count($fingerprint) !== 32) {
            throw new InvalidArgumentException('Fingerprint payload must contain 32 integers');
        }
        $columns = array('component_id');
        $placeholders = array(':component_id');
        for ($i = 0; $i < 32; $i++) {
            $columns[] = sprintf('fp%d', $i);
            $placeholders[] = sprintf(':fp%d', $i);
        }
        $req = $this->Db->prepare(sprintf(
            'REPLACE INTO reaction_component_fingerprints (%s) VALUES (%s)',
            implode(', ', $columns),
            implode(', ', $placeholders),
        ));
        $req->bindValue(':component_id', $componentId, PDO::PARAM_INT);
        for ($i = 0; $i < 32; $i++) {
            $req->bindValue(sprintf(':fp%d', $i), (int) $fingerprint[$i], PDO::PARAM_INT);
        }
        $this->Db->execute($req);
    }
}
