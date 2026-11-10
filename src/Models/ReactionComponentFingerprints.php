<?php

/**
 * @copyright 2026 Nicolas CARPi
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use PDO;

use function sprintf;

/**
 * Fingerprints of reaction components, for substructure search. Same layout as compounds_fingerprints.
 */
final class ReactionComponentFingerprints
{
    private Db $Db;

    private FingerprintTable $Table;

    public function __construct()
    {
        $this->Db = Db::getConnection();
        $this->Table = FingerprintTable::forReactionComponents();
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
        $this->Table->replace($componentId, $fingerprint);
    }

    /** The components of a copied reaction have the same structures as the source ones, matched by their key */
    public function copy(int $sourceReaction, int $copyReaction): void
    {
        $req = $this->Db->prepare(sprintf(
            'INSERT INTO reaction_component_fingerprints (component_id, %s)
            SELECT copy.id, %s FROM reaction_components AS source
            INNER JOIN reaction_component_fingerprints AS f ON (f.component_id = source.id)
            INNER JOIN reaction_components AS copy ON (copy.reaction_id = :copy AND copy.component_key = source.component_key)
            WHERE source.reaction_id = :source',
            FingerprintTable::wordColumns(),
            FingerprintTable::wordColumns('f'),
        ));
        $req->bindValue(':copy', $copyReaction, PDO::PARAM_INT);
        $req->bindValue(':source', $sourceReaction, PDO::PARAM_INT);
        $this->Db->execute($req);
    }
}
