<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Enums\AccessType;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Models\Users\AnonymousUser;
use Elabftw\Models\Users\Users;
use PDO;

use function sprintf;

/**
 * Read and write permissions of a compound, with the same rules as the ones of an entry (see Permissions).
 * For a list of compounds, filter in SQL with getCanFilter() instead.
 */
final class CompoundPermissions
{
    private Db $Db;

    public function __construct(private Users $requester)
    {
        $this->Db = Db::getConnection();
    }

    /**
     * @param array $compound a row of the compounds table, with at least its owner, team and permission columns
     */
    public function forCompound(array $compound): AccessPermissions
    {
        // compounds are never shown to anonymous users, even with a public base
        if ($this->requester instanceof AnonymousUser) {
            return new AccessPermissions();
        }
        return new Permissions($this->requester, $compound)->forEntity();
    }

    /**
     * The condition on the compounds the requester can read, with the same rules as forCompound(), for a query where
     * the compounds table has this alias: for a JOIN, a WHERE or a CASE. The query must bind :userid.
     */
    public function getCanCondition(string $alias): string
    {
        // never for anonymous users: the placeholder stays, so the query binds :userid like for anyone else
        if ($this->requester instanceof AnonymousUser) {
            return sprintf('(0 = 1 AND %s.userid = :userid)', $alias);
        }
        return new CanSqlBuilder($this->requester, AccessType::Read, $alias)->getCanCondition();
    }

    public function getCanFilter(string $alias): string
    {
        return ' AND ' . $this->getCanCondition($alias);
    }

    public function canOrExplode(array $compound, AccessType $access): void
    {
        if (!$this->forCompound($compound)->fromCan($access)) {
            throw new ForbiddenException();
        }
    }

    /**
     * Check the access to a compound from its id only, for instance to link it
     *
     * @return array the columns of the compound needed to check its permissions, and its InChIKey
     */
    public function canOrExplodeFromId(int $id, AccessType $access): array
    {
        $req = $this->Db->prepare('SELECT id, userid, team, state, inchi_key, canread_base, canwrite_base, canread, canwrite FROM compounds WHERE id = :id');
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $compound = $this->Db->fetch($req);
        $this->canOrExplode($compound, $access);
        return $compound;
    }
}
