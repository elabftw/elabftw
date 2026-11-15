<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\QueryParamsInterface;
use Override;
use PDO;

use function _;
use function array_keys;
use function array_map;
use function array_unique;
use function array_values;
use function implode;
use function sprintf;

/**
 * The reference list of the GHS hazard (H and EUH) and precautionary (P) statements, filled by the migrations.
 * The statements of a compound are links to this list.
 */
final class GhsStatements extends AbstractRest
{
    #[Override]
    public function getApiPath(): string
    {
        return 'api/v2/ghs_statements/';
    }

    /**
     * The whole list is small (a few hundred rows), so the clients filter it themselves
     */
    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $req = $this->Db->prepare('SELECT id, code, kind, statement, is_deprecated FROM ghs_statements ORDER BY kind, code');
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    /**
     * Find a statement to add it by hand to a compound: a withdrawn code is refused
     */
    public function readOneFromCode(string $code): array
    {
        $req = $this->Db->prepare('SELECT id, code, kind, statement, is_deprecated FROM ghs_statements WHERE code = :code');
        $req->bindValue(':code', $code);
        $this->Db->execute($req);
        try {
            $statement = $this->Db->fetch($req);
        } catch (ResourceNotFoundException) {
            throw new ImproperActionException(sprintf(_('Unknown GHS statement: %s'), $code));
        }
        if ($statement['is_deprecated'] === 1) {
            throw new ImproperActionException(sprintf(_('The GHS statement %s has been withdrawn and cannot be added anymore.'), $code));
        }
        return $statement;
    }

    /**
     * Link statements to a compound. Unknown codes and statements already linked are ignored: imports bring what their source has.
     * The caller checks the write access to the compound.
     *
     * @param string[] $codes
     */
    public function linkToCompound(int $compoundId, array $codes): void
    {
        $codes = array_values(array_unique($codes));
        if ($codes === array()) {
            return;
        }
        $placeholders = array_map(static fn(int $key): string => ':code' . $key, array_keys($codes));
        $sql = sprintf(
            'INSERT IGNORE INTO compounds2ghs_statements (compound_id, statement_id)
                SELECT :compound_id, id FROM ghs_statements WHERE code IN (%s)',
            implode(', ', $placeholders),
        );
        $req = $this->Db->prepare($sql);
        $req->bindValue(':compound_id', $compoundId, PDO::PARAM_INT);
        foreach ($codes as $key => $code) {
            $req->bindValue(':code' . $key, $code);
        }
        $this->Db->execute($req);
    }

    /**
     * The statements of several compounds in one query, ordered hazard first, then EU hazard, then precautionary.
     * The caller checks the read access to the compounds.
     *
     * @param int[] $compoundIds
     * @return array<int, list<array>> the statements, by compound id
     */
    public function readForCompounds(array $compoundIds): array
    {
        $compoundIds = array_values(array_unique(array_map('intval', $compoundIds)));
        if ($compoundIds === array()) {
            return array();
        }
        $placeholders = array_map(static fn(int $key): string => ':id' . $key, array_keys($compoundIds));
        $sql = sprintf(
            'SELECT c2gs.compound_id, gs.id, gs.code, gs.kind, gs.statement, gs.is_deprecated
                FROM compounds2ghs_statements AS c2gs
                INNER JOIN ghs_statements AS gs ON (gs.id = c2gs.statement_id)
                WHERE c2gs.compound_id IN (%s)
                ORDER BY gs.kind, gs.code',
            implode(', ', $placeholders),
        );
        $req = $this->Db->prepare($sql);
        foreach ($compoundIds as $key => $id) {
            $req->bindValue(':id' . $key, $id, PDO::PARAM_INT);
        }
        $this->Db->execute($req);
        $byCompound = array();
        foreach ($req->fetchAll() as $row) {
            $compoundId = (int) $row['compound_id'];
            unset($row['compound_id']);
            $byCompound[$compoundId][] = $row;
        }
        return $byCompound;
    }
}
