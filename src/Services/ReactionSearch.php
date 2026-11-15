<?php

/**
 * @copyright 2026 Nicolas CARPi
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\CanSqlBuilder;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\AccessType;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\FingerprinterInterface;
use Elabftw\Interfaces\SmartsMatcherInterface;
use Elabftw\Models\FingerprintTable;
use Elabftw\Models\Users\Users;
use Elabftw\Params\ReactionParams;
use PDO;
use PDOStatement;
use Symfony\Component\HttpFoundation\InputBag;

use function _;
use function addcslashes;
use function array_keys;
use function array_push;
use function array_sum;
use function count;
use function filter_var;
use function implode;
use function in_array;
use function mb_strlen;
use function preg_match;
use function sprintf;
use function strlen;
use function strtoupper;
use function substr;
use function trim;

/**
 * Filter a listing to the entries of a type that contain a current (not deleted) reaction matching all the criteria.
 * It is a subquery of the listing, which applies the read permissions and the other filters: the matching ids never
 * go through PHP, however many there are. The listing binds the values with bindValues().
 *
 * - reaction_q: text in the title, conditions or atmosphere of the reaction, or the name or formula of a component
 * - reaction_structure: SMILES or InChIKey of a component; reaction_exact switches from related to identical structures
 * - reaction_structure_type: smarts to read reaction_structure as a SMARTS pattern instead (reaction_exact does not apply)
 * - reaction_compound, reaction_item: a component linked to this compound or resource
 * - reaction_role: restricts the three component criteria above to components with that role
 * - reaction_key: the same transformation (reactants and products), see ReactionIdentity::key()
 */
final class ReactionSearch
{
    public const array PARAMS = array('reaction_q', 'reaction_structure', 'reaction_structure_type', 'reaction_exact', 'reaction_role', 'reaction_compound', 'reaction_item', 'reaction_key');

    private const string INCHIKEY_REGEX = '/^[A-Z]{14}-[A-Z]{10}-[A-Z]$/';

    private const int MAX_TEXT_LENGTH = 255;

    private const int MAX_STRUCTURE_LENGTH = 2048;

    // every candidate structure goes through Open Babel on each listing request: above this, ask for narrower criteria
    private const int MAX_SMARTS_CANDIDATES = 20000;

    private string $text;

    private string $structure;

    private bool $exact;

    private bool $smarts = false;

    private ?string $role = null;

    private ?int $compound = null;

    private ?int $item = null;

    private ?string $key = null;

    /** Built on first use, with its binds: a structure search runs Open Babel */
    private ?string $filterSql = null;

    private array $binds = array();

    /** The criteria on the reaction itself (key, text), for the candidates of a SMARTS search too */
    private string $reactionSql = '';

    public function __construct(
        private EntityType $entityType,
        InputBag $query,
        private FingerprinterInterface $fingerprinter = new OpenBabelFingerprinter(),
        private SmartsMatcherInterface $smartsMatcher = new OpenBabelSmartsMatcher(),
        // what the listing shows: the candidates of a SMARTS search are only taken from these entries, see getSmartsSql()
        private ?Users $requester = null,
        private string $entityScopeSql = '',
    ) {
        $this->text = trim($query->getString('reaction_q'));
        if (mb_strlen($this->text) > self::MAX_TEXT_LENGTH) {
            throw new ImproperActionException(sprintf(_('The reaction search text is limited to %d characters.'), self::MAX_TEXT_LENGTH));
        }
        $this->structure = trim($query->getString('reaction_structure'));
        // the structure goes to Open Babel on every listing request: keep it to the size of a drawn molecule
        if (strlen($this->structure) > self::MAX_STRUCTURE_LENGTH) {
            throw new ImproperActionException(sprintf(_('The reaction search structure is limited to %d characters.'), self::MAX_STRUCTURE_LENGTH));
        }
        $structureType = $query->getString('reaction_structure_type');
        if ($structureType !== '') {
            if ($structureType !== 'smarts') {
                throw new ImproperActionException('Invalid reaction structure type.');
            }
            $this->smarts = true;
        }
        $this->exact = filter_var($query->getString('reaction_exact'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
        $role = $query->getString('reaction_role');
        if ($role !== '') {
            if (!in_array($role, ReactionParams::ROLES, true)) {
                throw new ImproperActionException('Invalid reaction component role.');
            }
            $this->role = $role;
        }
        $this->compound = $this->getId($query, 'reaction_compound');
        $this->item = $this->getId($query, 'reaction_item');
        $key = $query->getString('reaction_key');
        if ($key !== '') {
            if (preg_match('/^[0-9a-f]{64}$/', $key) !== 1) {
                throw new ImproperActionException('Invalid reaction key.');
            }
            $this->key = $key;
        }
    }

    /** A role or the exact flag alone only refine the other criteria */
    public function isActive(): bool
    {
        return $this->text !== '' || $this->hasComponentCriteria() || $this->key !== null;
    }

    /** Condition on entity.id for the listing query, empty without search criteria */
    public function getFilterSql(): string
    {
        if (!$this->isActive()) {
            return '';
        }
        return $this->filterSql ??= $this->buildFilterSql();
    }

    /** Call it on the statement that contains getFilterSql() */
    public function bindValues(PDOStatement $req): void
    {
        $this->getFilterSql();
        foreach ($this->binds as $name => $bind) {
            $req->bindValue($name, $bind[0], $bind[1]);
        }
    }

    /** The placeholders are prefixed so they cannot collide with the ones of the listing query */
    private function buildFilterSql(): string
    {
        $this->binds = array(':rs_state' => array(State::Normal->value, PDO::PARAM_INT));
        $sql = sprintf(
            ' AND entity.id IN (SELECT links.entity_id FROM reactions2%s AS links
            INNER JOIN reactions AS r ON (r.id = links.reaction_id AND r.state = :rs_state)
            WHERE 1=1',
            $this->entityType->value,
        );
        if ($this->key !== null) {
            $this->reactionSql .= ' AND r.reaction_key = :rs_key';
            $this->binds[':rs_key'] = array($this->key, PDO::PARAM_STR);
        }
        if ($this->text !== '') {
            $this->reactionSql .= ' AND (r.title LIKE :rs_text_title OR r.conditions_notes LIKE :rs_text_notes OR r.atmosphere LIKE :rs_text_atmosphere
                OR EXISTS (SELECT 1 FROM reaction_components AS tc WHERE tc.reaction_id = r.id
                    AND (tc.name LIKE :rs_text_name OR tc.molecular_formula LIKE :rs_text_formula)))';
            $like = '%' . addcslashes($this->text, '%_\\') . '%';
            foreach (array('title', 'notes', 'atmosphere', 'name', 'formula') as $field) {
                $this->binds[':rs_text_' . $field] = array($like, PDO::PARAM_STR);
            }
        }
        $sql .= $this->reactionSql;
        if ($this->hasComponentCriteria()) {
            $sql .= $this->getComponentSql();
        }
        return $sql . ')';
    }

    private function hasComponentCriteria(): bool
    {
        return $this->structure !== '' || $this->compound !== null || $this->item !== null;
    }

    /** All the component criteria apply to the same component */
    private function getComponentSql(): string
    {
        $join = '';
        $where = '';
        if ($this->role !== null) {
            $where .= ' AND c.role = :rs_role';
            $this->binds[':rs_role'] = array($this->role, PDO::PARAM_STR);
        }
        if ($this->compound !== null) {
            $where .= ' AND c.compound_id = :rs_compound';
            $this->binds[':rs_compound'] = array($this->compound, PDO::PARAM_INT);
        }
        if ($this->item !== null) {
            $where .= ' AND c.item_id = :rs_item';
            $this->binds[':rs_item'] = array($this->item, PDO::PARAM_INT);
        }
        if ($this->structure !== '' && $this->smarts) {
            $where .= $this->getSmartsSql($where, $this->structure);
        } elseif ($this->structure !== '') {
            if (preg_match(self::INCHIKEY_REGEX, strtoupper($this->structure)) === 1) {
                $inchiKey = strtoupper($this->structure);
                if ($this->exact) {
                    $where .= ' AND c.inchi_key = :rs_inchi_key';
                    $this->binds[':rs_inchi_key'] = array($inchiKey, PDO::PARAM_STR);
                } else {
                    // the first block encodes the connectivity: also match stereoisomers, isotopologues and other protonation states
                    $where .= ' AND c.inchi_key LIKE :rs_inchi_key';
                    $this->binds[':rs_inchi_key'] = array(substr($inchiKey, 0, 14) . '-%', PDO::PARAM_STR);
                }
            } else {
                $smiles = ReactionIdentity::normalizeSmiles($this->structure);
                $fingerprint = new ReactionFingerprinter($this->fingerprinter)->queryFingerprint($smiles);
                if ($fingerprint === null) {
                    throw new ImproperActionException(_('Could not read this structure: enter a SMILES or an InChIKey.'));
                }
                if (array_sum($fingerprint) === 0) {
                    // an empty fingerprint (water, ammonia…) screens nothing: match the structure itself, a SMILES is also a SMARTS
                    $where .= $this->getSmartsSql($where, $smiles, $this->exact);
                } else {
                    $join = ' INNER JOIN reaction_component_fingerprints AS f ON (f.component_id = c.id)';
                    $where .= $this->getFingerprintSql($fingerprint);
                }
            }
        }
        return ' AND EXISTS (SELECT 1 FROM reaction_components AS c' . $join . ' WHERE c.reaction_id = r.id' . $where . ')';
    }

    /**
     * Fingerprint screening, like the compounds search: a component containing the query structure
     * has at least all its bits. It can return false positives, never false negatives.
     */
    private function getFingerprintSql(array $fingerprint): string
    {
        return $this->exact
            ? FingerprintTable::equalsSql($fingerprint, 'f')
            : FingerprintTable::containsSql($fingerprint, 'f');
    }

    /**
     * SMARTS cannot be screened with fingerprints: the candidate components are read, and Open Babel tells which ones match.
     * The candidates already satisfy everything else the listing can check in SQL (permissions, scope, owner, state…, and the
     * other reaction and component criteria), so the limit is reached because of what is searched, not because of other entries.
     * Each distinct structure is tested once, and the limit bounds that work. The matching structures are given by their hash:
     * a SMILES can be long, the hash is safe to inline.
     *
     * @param string $where the other component criteria, with their binds already in $this->binds
     */
    private function getSmartsSql(string $where, string $pattern, bool $exact = false): string
    {
        $entityJoin = '';
        $entityScope = '';
        if ($this->requester !== null) {
            $entityJoin = sprintf(' INNER JOIN %s AS entity ON (entity.id = links.entity_id)', $this->entityType->value);
            // a superset of what the listing shows, which applies its own permissions and filters on the result
            $entityScope = $this->entityScopeSql . sprintf(
                ' AND (%s OR entity.userid = :userid)',
                new CanSqlBuilder($this->requester, AccessType::Read)->getCanCondition(),
            );
        }
        $sql = sprintf(
            "SELECT SHA2(c.smiles, 256) AS smiles_hash, ANY_VALUE(c.smiles) AS smiles FROM reaction_components AS c
            INNER JOIN reactions AS r ON (r.id = c.reaction_id AND r.state = :rs_state)
            INNER JOIN reactions2%s AS links ON (links.reaction_id = r.id)%s
            WHERE c.smiles != ''%s%s%s
            GROUP BY smiles_hash LIMIT %d",
            $this->entityType->value,
            $entityJoin,
            $where,
            $this->reactionSql,
            $entityScope,
            self::MAX_SMARTS_CANDIDATES + 1,
        );
        $Db = Db::getConnection();
        $req = $Db->prepare($sql);
        // only the binds of this query: the ones of the other criteria can be absent from it
        foreach ($this->binds as $name => $bind) {
            if (preg_match('/' . $name . '\b/', $sql) === 1) {
                $req->bindValue($name, $bind[0], $bind[1]);
            }
        }
        if ($this->requester !== null) {
            $req->bindValue(':userid', $this->requester->userid, PDO::PARAM_INT);
        }
        $Db->execute($req);
        $rows = $req->fetchAll();
        if (count($rows) > self::MAX_SMARTS_CANDIDATES) {
            throw new ImproperActionException(_('Too many structures to search with a SMARTS pattern: select a role or add other criteria.'));
        }
        // different stored texts can be the same SMILES once normalized
        $hashesBySmiles = array();
        foreach ($rows as $row) {
            $smiles = ReactionIdentity::normalizeSmiles((string) $row['smiles']);
            if ($smiles !== '' && preg_match('/^[0-9a-f]{64}$/', (string) $row['smiles_hash']) === 1) {
                $hashesBySmiles[$smiles][] = $row['smiles_hash'];
            }
        }
        $structures = array_keys($hashesBySmiles);
        $hashes = array();
        foreach ($this->smartsMatcher->matches($pattern, $structures, $exact) as $index) {
            array_push($hashes, ...$hashesBySmiles[$structures[$index]]);
        }
        if ($hashes === array()) {
            return ' AND 0 = 1';
        }
        return sprintf(" AND SHA2(c.smiles, 256) IN ('%s')", implode("', '", $hashes));
    }

    private function getId(InputBag $query, string $name): ?int
    {
        $value = $query->getString($name);
        if ($value === '') {
            return null;
        }
        // not getInt(): it silently turns an invalid value into 0
        $id = filter_var($value, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
        if ($id === false) {
            throw new ImproperActionException(sprintf('Invalid %s.', $name));
        }
        return $id;
    }
}
