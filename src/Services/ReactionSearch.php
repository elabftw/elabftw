<?php

/**
 * @copyright 2026 Nicolas CARPi
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\FingerprinterInterface;
use Elabftw\Params\ReactionParams;
use PDO;
use Symfony\Component\HttpFoundation\InputBag;

use function _;
use function addcslashes;
use function filter_var;
use function array_map;
use function in_array;
use function intval;
use function mb_strlen;
use function preg_match;
use function sprintf;
use function strtoupper;
use function substr;
use function trim;

/**
 * Find the entries of a type that contain a current (not deleted) reaction matching all the criteria.
 * It only selects candidate ids: the listing query applies the read permissions and the other filters.
 *
 * - reaction_q: text in the title, conditions or atmosphere of the reaction, or the name or formula of a component
 * - reaction_structure: SMILES or InChIKey of a component; reaction_exact switches from related to identical structures
 * - reaction_compound, reaction_item: a component linked to this compound or resource
 * - reaction_role: restricts the three component criteria above to components with that role
 * - reaction_key: the same transformation (reactants and products), see ReactionIdentity::key()
 */
final class ReactionSearch
{
    public const array PARAMS = array('reaction_q', 'reaction_structure', 'reaction_exact', 'reaction_role', 'reaction_compound', 'reaction_item', 'reaction_key');

    private const string INCHIKEY_REGEX = '/^[A-Z]{14}-[A-Z]{10}-[A-Z]$/';

    private const int MAX_TEXT_LENGTH = 255;

    private string $text;

    private string $structure;

    private bool $exact;

    private ?string $role = null;

    private ?int $compound = null;

    private ?int $item = null;

    private ?string $key = null;

    public function __construct(
        private EntityType $entityType,
        InputBag $query,
        private FingerprinterInterface $fingerprinter = new OpenBabelFingerprinter(),
    ) {
        $this->text = trim($query->getString('reaction_q'));
        if (mb_strlen($this->text) > self::MAX_TEXT_LENGTH) {
            throw new ImproperActionException(sprintf(_('The reaction search text is limited to %d characters.'), self::MAX_TEXT_LENGTH));
        }
        $this->structure = trim($query->getString('reaction_structure'));
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

    public function getEntityIds(): array
    {
        if (!$this->isActive()) {
            throw new ImproperActionException('No reaction search criteria.');
        }
        $binds = array(':state' => array(State::Normal->value, PDO::PARAM_INT));
        $sql = sprintf(
            'SELECT DISTINCT links.entity_id FROM reactions2%s AS links
            INNER JOIN reactions AS r ON (r.id = links.reaction_id AND r.state = :state)
            WHERE 1=1',
            $this->entityType->value,
        );
        if ($this->key !== null) {
            $sql .= ' AND r.reaction_key = :reaction_key';
            $binds[':reaction_key'] = array($this->key, PDO::PARAM_STR);
        }
        if ($this->text !== '') {
            $sql .= ' AND (r.title LIKE :text_title OR r.conditions_notes LIKE :text_notes OR r.atmosphere LIKE :text_atmosphere
                OR EXISTS (SELECT 1 FROM reaction_components AS tc WHERE tc.reaction_id = r.id
                    AND (tc.name LIKE :text_name OR tc.molecular_formula LIKE :text_formula)))';
            $like = '%' . addcslashes($this->text, '%_\\') . '%';
            foreach (array('title', 'notes', 'atmosphere', 'name', 'formula') as $field) {
                $binds[':text_' . $field] = array($like, PDO::PARAM_STR);
            }
        }
        if ($this->hasComponentCriteria()) {
            $sql .= $this->getComponentSql($binds);
        }

        $Db = Db::getConnection();
        $req = $Db->prepare($sql);
        foreach ($binds as $name => $bind) {
            $req->bindValue($name, $bind[0], $bind[1]);
        }
        $Db->execute($req);
        return array_map(intval(...), $req->fetchAll(PDO::FETCH_COLUMN));
    }

    private function hasComponentCriteria(): bool
    {
        return $this->structure !== '' || $this->compound !== null || $this->item !== null;
    }

    /** All the component criteria apply to the same component */
    private function getComponentSql(array &$binds): string
    {
        $join = '';
        $where = '';
        if ($this->role !== null) {
            $where .= ' AND c.role = :role';
            $binds[':role'] = array($this->role, PDO::PARAM_STR);
        }
        if ($this->compound !== null) {
            $where .= ' AND c.compound_id = :compound';
            $binds[':compound'] = array($this->compound, PDO::PARAM_INT);
        }
        if ($this->item !== null) {
            $where .= ' AND c.item_id = :item';
            $binds[':item'] = array($this->item, PDO::PARAM_INT);
        }
        if ($this->structure !== '') {
            if (preg_match(self::INCHIKEY_REGEX, strtoupper($this->structure)) === 1) {
                $inchiKey = strtoupper($this->structure);
                if ($this->exact) {
                    $where .= ' AND c.inchi_key = :inchi_key';
                    $binds[':inchi_key'] = array($inchiKey, PDO::PARAM_STR);
                } else {
                    // the first block encodes the connectivity: also match stereoisomers, isotopologues and other protonation states
                    $where .= ' AND c.inchi_key LIKE :inchi_key';
                    $binds[':inchi_key'] = array(substr($inchiKey, 0, 14) . '-%', PDO::PARAM_STR);
                }
            } else {
                $join = ' INNER JOIN reaction_component_fingerprints AS f ON (f.component_id = c.id)';
                $where .= $this->getFingerprintSql();
            }
        }
        return ' AND EXISTS (SELECT 1 FROM reaction_components AS c' . $join . ' WHERE c.reaction_id = r.id' . $where . ')';
    }

    /**
     * Fingerprint screening, like the compounds search: a component containing the query structure
     * has at least all its bits. It can return false positives, never false negatives.
     */
    private function getFingerprintSql(): string
    {
        $fingerprint = new ReactionFingerprinter($this->fingerprinter)->fingerprint(ReactionIdentity::normalizeSmiles($this->structure));
        if ($fingerprint === null) {
            throw new ImproperActionException(_('Could not read this structure: enter a SMILES or an InChIKey.'));
        }
        $sql = '';
        foreach ($fingerprint as $index => $value) {
            if ($this->exact) {
                $sql .= sprintf(' AND f.fp%d = %d', $index, $value);
            } elseif ($value !== 0) {
                $sql .= sprintf(' AND (f.fp%d & %d) = %d', $index, $value, $value);
            }
        }
        return $sql;
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
