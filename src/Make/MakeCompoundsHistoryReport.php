<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use DateTimeImmutable;
use Elabftw\Elabftw\CompoundPermissions;
use Elabftw\Enums\State;
use Elabftw\Models\Compounds;
use Elabftw\Models\GhsStatements;
use Elabftw\Services\ReactionCalculator;
use Override;
use PDO;
use PDOStatement;

use function array_column;
use function array_fill_keys;
use function array_filter;
use function array_intersect_key;
use function array_map;
use function array_merge;
use function implode;
use function sprintf;
use function strcmp;
use function usort;

/**
 * Make a CSV file with all the compounds manipulated by a user over a period: the ones linked to their experiments,
 * directly or through a linked resource, and the ones used in the reactions of these experiments, with their quantities
 * and their hazards.
 */
final class MakeCompoundsHistoryReport extends MakeCompoundsReport
{
    /** Where the compound comes from, in the source column */
    private const string SOURCE_EXPERIMENT = 'experiment';

    private const string SOURCE_RESOURCE = 'linked_resource';

    private const string SOURCE_REACTION = 'reaction';

    /** The columns of the compounds table that describe the compound, in the order they appear in the file */
    private const array COMPOUND_COLUMNS = array(
        'name', 'iupac_name', 'molecular_formula', 'molecular_weight', 'cas_number', 'ec_number', 'pubchem_cid',
        'inchi', 'inchi_key', 'smiles', 'chebi_id', 'chembl_id', 'dea_number', 'drugbank_id', 'dsstox_id', 'hmdb_id',
        'kegg_id', 'metabolomics_wb_id', 'nci_code', 'nikkaji_number', 'pharmgkb_id', 'pharos_ligand_id', 'rxcui',
        'unii', 'wikidata', 'wikipedia',
    );

    private const array HAZARD_COLUMNS = array(
        'is_corrosive', 'is_explosive', 'is_flammable', 'is_gas_under_pressure', 'is_hazardous2env', 'is_hazardous2health',
        'is_serious_health_hazard', 'is_oxidising', 'is_toxic', 'is_radioactive', 'is_antibiotic', 'is_antibiotic_precursor',
        'is_drug', 'is_drug_precursor', 'is_explosive_precursor', 'is_cmr', 'is_nano', 'is_controlled', 'is_ed2health',
        'is_ed2env', 'is_pbt', 'is_pmt', 'is_vpvb', 'is_vpvm',
    );

    /** A reaction component without a linked compound still has its own structure: it fills these compound columns */
    private const array COMPONENT_IDENTITY_COLUMNS = array('name', 'molecular_formula', 'molecular_weight', 'smiles', 'inchi_key');

    public function __construct(protected Compounds $compounds, private DateTimeImmutable $start, private DateTimeImmutable $end)
    {
        parent::__construct($compounds);
    }

    #[Override]
    protected function getData(): array
    {
        $linked = $this->readLinkedCompounds();
        $reactions = $this->readReactionComponents();
        $ghs = new GhsStatements()->readForCompounds(array_filter(array_merge(
            array_column($linked, 'compound_id'),
            array_column($reactions, 'compound_id'),
        )));
        $rows = array_map(
            static fn(array $row): array => self::toRow($row, $ghs[(int) $row['compound_id']] ?? array()),
            array_merge($linked, $reactions),
        );
        // the sort is stable: in an experiment, the linked compounds stay before the reaction components, in their order
        usort($rows, static fn(array $a, array $b): int => strcmp((string) $b['experiment_date'], (string) $a['experiment_date'])
            ?: $b['experiment_id'] <=> $a['experiment_id']);
        return $rows;
    }

    /**
     * The compounds linked to the experiments, directly or through a linked resource
     */
    private function readLinkedCompounds(): array
    {
        $sql = sprintf(
            'SELECT
              %1$s,
              c.id         AS compound_id,
              e.id         AS experiment_id,
              e.title      AS experiment_title,
              e.date       AS experiment_date,
              e.created_at AS experiment_created_at,
              ec.source,
              ec.resource_id,
              i.title      AS resource_title
            FROM experiments AS e
            JOIN (
              SELECT c2e.entity_id AS experiment_id, c2e.compound_id, \'%2$s\' AS source, NULL AS resource_id
              FROM compounds2experiments AS c2e

              UNION DISTINCT

              SELECT e2i.item_id AS experiment_id, c2i.compound_id, \'%3$s\' AS source, e2i.link_id AS resource_id
              FROM experiments2items AS e2i
              JOIN compounds2items   AS c2i
                ON c2i.entity_id = e2i.link_id
            ) AS ec
              ON ec.experiment_id = e.id
            JOIN compounds AS c
              ON c.id = ec.compound_id
            LEFT JOIN items AS i
              ON i.id = ec.resource_id
            WHERE e.userid = :userid
              %4$s
              AND DATE(e.date) >= :start
              AND DATE(e.date) <= :end
            ORDER BY e.date DESC, e.id DESC, ec.source ASC, c.name ASC',
            $this->getCompoundSelect(),
            self::SOURCE_EXPERIMENT,
            self::SOURCE_RESOURCE,
            // the experiments are the ones of the requester, but not necessarily the compounds linked to them
            new CompoundPermissions($this->compounds->requester)->getCanFilter('c'),
        );
        $req = $this->Db->prepare($sql);
        $this->bindCommon($req);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    /**
     * The components of the current reactions of the experiments, with their quantities. A component is listed even
     * without a linked compound, or with one the requester cannot read: it was manipulated all the same, and its
     * structure is in the reaction of their own experiment. Only the columns of the compound itself stay empty.
     */
    private function readReactionComponents(): array
    {
        $sql = sprintf(
            'SELECT
              %1$s,
              c.id                        AS compound_id,
              e.id                        AS experiment_id,
              e.title                     AS experiment_title,
              e.date                      AS experiment_date,
              e.created_at                AS experiment_created_at,
              \'%3$s\'                    AS source,
              rc.item_id                  AS resource_id,
              i.title                     AS resource_title,
              r.id                        AS reaction_id,
              r.title                     AS reaction_title,
              r.reaction_smiles,
              r.temperature_c             AS reaction_temperature_c,
              r.duration_s                AS reaction_duration_s,
              r.pressure_pa               AS reaction_pressure_pa,
              r.atmosphere                AS reaction_atmosphere,
              r.conditions_notes          AS reaction_conditions_notes,
              r.reference_component_key,
              r.yield_basis_component_key,
              rc.component_key,
              rc.role,
              rc.name                     AS component_name,
              rc.smiles                   AS component_smiles,
              rc.inchi_key                AS component_inchi_key,
              rc.molecular_formula        AS component_molecular_formula,
              rc.molecular_weight         AS component_molecular_weight,
              rc.material_form,
              rc.purity_percent,
              rc.density_g_ml,
              rc.concentration_mol_l,
              rc.coefficient,
              rc.planned_basis,
              rc.planned_unit,
              rc.planned_value,
              rc.actual_basis,
              rc.actual_unit,
              rc.actual_value
            FROM experiments AS e
            JOIN reactions2experiments AS r2e
              ON r2e.entity_id = e.id
            JOIN reactions AS r
              ON r.id = r2e.reaction_id AND r.state = :state
            JOIN reaction_components AS rc
              ON rc.reaction_id = r.id
            LEFT JOIN compounds AS c
              ON c.id = rc.compound_id AND %2$s
            LEFT JOIN items AS i
              ON i.id = rc.item_id
            WHERE e.userid = :userid
              AND DATE(e.date) >= :start
              AND DATE(e.date) <= :end
            ORDER BY e.date DESC, e.id DESC, r2e.ordering ASC, r.id ASC, rc.ordering ASC, rc.id ASC',
            $this->getCompoundSelect(),
            new CompoundPermissions($this->compounds->requester)->getCanCondition('c'),
            self::SOURCE_REACTION,
        );
        $req = $this->Db->prepare($sql);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $this->bindCommon($req);
        $this->Db->execute($req);
        $components = $req->fetchAll();

        // the calculator needs all the components of a reaction at once: the equivalents depend on the reference component
        $byReaction = array();
        foreach ($components as $component) {
            $byReaction[$component['reaction_id']][] = $component;
        }
        $calculated = array();
        $calculator = new ReactionCalculator();
        foreach ($byReaction as $reactionComponents) {
            foreach ($calculator->calculate($reactionComponents[0], array_map(self::toCalculatorInput(...), $reactionComponents)) as $key => $result) {
                $calculated[] = array_merge($reactionComponents[$key], array('planned' => $result['planned'], 'actual' => $result['actual']));
            }
        }
        return $calculated;
    }

    private function getCompoundSelect(): string
    {
        return implode(', ', array_map(
            static fn(string $column): string => sprintf('c.%1$s AS %1$s', $column),
            array_merge(self::COMPOUND_COLUMNS, self::HAZARD_COLUMNS, array('ghs_signal_word')),
        ));
    }

    private function bindCommon(PDOStatement $req): void
    {
        $req->bindValue(':userid', $this->compounds->requester->userid, PDO::PARAM_INT);
        $req->bindValue(':start', $this->start->format('Y-m-d'));
        $req->bindValue(':end', $this->end->format('Y-m-d'));
    }

    /**
     * The calculator works on the columns of reaction_components: the component ones are prefixed in the query
     */
    private static function toCalculatorInput(array $component): array
    {
        return array_merge($component, array(
            'molecular_weight' => $component['component_molecular_weight'],
        ));
    }

    /**
     * Every row has the same columns, in the same order, whatever its source: the header is read from the first one
     */
    private static function toRow(array $data, array $statements): array
    {
        $isReaction = $data['source'] === self::SOURCE_REACTION;
        $compound = array_intersect_key($data, array_fill_keys(self::COMPOUND_COLUMNS, null));
        if ($isReaction && $data['compound_id'] === null) {
            foreach (self::COMPONENT_IDENTITY_COLUMNS as $column) {
                $compound[$column] = $data['component_' . $column];
            }
        }
        $hazards = array_filter($statements, static fn(array $s): bool => $s['kind'] !== 'P');
        $precautions = array_filter($statements, static fn(array $s): bool => $s['kind'] === 'P');
        $planned = $data['planned'] ?? array();
        $actual = $data['actual'] ?? array();
        $reaction = static fn(string $key): mixed => $isReaction ? $data[$key] : null;
        $component = $data['component_key'] ?? null;

        return array_merge(
            array(
                'experiment_id' => $data['experiment_id'],
                'experiment_title' => $data['experiment_title'],
                'experiment_date' => $data['experiment_date'],
                'experiment_created_at' => $data['experiment_created_at'],
                'source' => $data['source'],
                'resource_id' => $data['resource_id'],
                'resource_title' => $data['resource_title'],
                'reaction_id' => $reaction('reaction_id'),
                'reaction_title' => $reaction('reaction_title'),
                'reaction_smiles' => $reaction('reaction_smiles'),
                'reaction_temperature_c' => $reaction('reaction_temperature_c'),
                'reaction_duration_s' => $reaction('reaction_duration_s'),
                'reaction_pressure_pa' => $reaction('reaction_pressure_pa'),
                'reaction_atmosphere' => $reaction('reaction_atmosphere'),
                'reaction_conditions_notes' => $reaction('reaction_conditions_notes'),
                'role' => $reaction('role'),
                'is_reference_component' => $isReaction ? (int) ($component === $data['reference_component_key']) : null,
                'is_yield_basis_component' => $isReaction ? (int) ($component === $data['yield_basis_component_key']) : null,
                'component_name' => $reaction('component_name'),
                'material_form' => $reaction('material_form'),
                'purity_percent' => $reaction('purity_percent'),
                'density_g_ml' => $reaction('density_g_ml'),
                'concentration_mol_l' => $reaction('concentration_mol_l'),
                'stoichiometric_coefficient' => $reaction('coefficient'),
                'planned_value' => $reaction('planned_value'),
                'planned_unit' => $reaction('planned_unit'),
                'planned_mass_g' => $planned['mass_g'] ?? null,
                'planned_volume_ml' => $planned['volume_ml'] ?? null,
                'planned_amount_mol' => $planned['amount_mol'] ?? null,
                'planned_equivalents' => $planned['equivalents'] ?? null,
                'actual_value' => $reaction('actual_value'),
                'actual_unit' => $reaction('actual_unit'),
                'actual_mass_g' => $actual['mass_g'] ?? null,
                'actual_volume_ml' => $actual['volume_ml'] ?? null,
                'actual_amount_mol' => $actual['amount_mol'] ?? null,
                'actual_equivalents' => $actual['equivalents'] ?? null,
                'theoretical_amount_mol' => $actual['theoretical_amount_mol'] ?? null,
                'yield_percent' => $actual['yield_percent'] ?? null,
                'compound_id' => $data['compound_id'],
            ),
            array_merge(array_fill_keys(self::COMPOUND_COLUMNS, null), $compound),
            array(
                'ghs_signal_word' => $data['ghs_signal_word'],
                'ghs_hazard_codes' => self::joinColumn($hazards, 'code', ', '),
                'ghs_hazard_statements' => self::joinColumn($hazards, 'statement', ' '),
                'ghs_precautionary_codes' => self::joinColumn($precautions, 'code', ', '),
                'ghs_precautionary_statements' => self::joinColumn($precautions, 'statement', ' '),
            ),
            array_merge(array_fill_keys(self::HAZARD_COLUMNS, null), array_intersect_key($data, array_fill_keys(self::HAZARD_COLUMNS, null))),
        );
    }

    private static function joinColumn(array $statements, string $column, string $separator): string
    {
        return implode($separator, array_map(
            static fn(array $statement): string => $column === 'statement'
                ? sprintf('%s: %s', $statement['code'], $statement['statement'])
                : $statement['code'],
            $statements,
        ));
    }
}
