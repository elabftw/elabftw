<?php

/**
 * @copyright 2026 Nicolas CARPi
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Params;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\Filter;
use Symfony\Component\Uid\Uuid;

use function array_is_list;
use function base64_decode;
use function count;
use function filter_var;
use function getimagesizefromstring;
use function in_array;
use function is_array;
use function is_finite;
use function is_float;
use function is_int;
use function is_numeric;
use function is_string;
use function json_encode;
use function rtrim;
use function str_contains;
use function strlen;
use function ltrim;
use function preg_match;
use function str_pad;
use function str_repeat;
use function substr;

/**
 * Validate the whole reaction before writing any of it. Chemical properties are
 * local snapshots; linking a compound never refreshes an existing experiment.
 */
final class ReactionParams
{
    public const array ROLES = array('reactant', 'reagent', 'catalyst', 'solvent', 'product');

    public const array UNITS = array('g' => array('mass', 1.0), 'mg' => array('mass', 0.001), 'mL' => array('volume', 1.0), 'uL' => array('volume', 0.001), 'mol' => array('amount', 1.0), 'mmol' => array('amount', 0.001), 'eq' => array('equivalents', 1.0));

    public array $reaction;

    public array $components;

    public function __construct(array $data)
    {
        $scheme = $data['scheme'] ?? null;
        if ($scheme !== null && (!is_array($scheme) || !isset($scheme['root']['nodes']) || !is_array($scheme['root']['nodes']))) {
            throw new ImproperActionException('Invalid KET document.');
        }
        $this->reaction = array(
            'title' => Filter::title(self::text($data['title'] ?? '', 1024)),
            'scheme' => $scheme === null ? null : self::text(json_encode($scheme, JSON_THROW_ON_ERROR), 2_000_000),
            'rxn' => self::text($data['rxn'] ?? '', 2_000_000),
            'preview_png' => self::preview($data['preview_png'] ?? ''),
            'temperature_c' => self::number($data['temperature_c'] ?? null, -273.15),
            'duration_s' => self::number($data['duration_s'] ?? null),
            'pressure_pa' => self::number($data['pressure_pa'] ?? null),
            'atmosphere' => self::text($data['atmosphere'] ?? '', 255),
            'conditions_notes' => self::text($data['conditions_notes'] ?? '', 60_000),
            'reference_component_key' => $data['reference_component_key'] ?? null,
            'yield_basis_component_key' => $data['yield_basis_component_key'] ?? null,
        );
        $components = $data['components'] ?? array();
        if (!is_array($components) || !array_is_list($components) || count($components) > 128) {
            throw new ImproperActionException('A reaction can contain at most 128 components.');
        }
        $this->components = array();
        $keys = array();
        foreach ($components as $component) {
            if (!is_array($component)) {
                throw new ImproperActionException('Invalid reaction component.');
            }
            $key = $component['component_key'] ?? Uuid::v4()->toRfc4122();
            if (!is_string($key) || !Uuid::isValid($key) || isset($keys[$key])) {
                throw new ImproperActionException('Reaction component keys must be unique UUIDs.');
            }
            $role = $component['role'] ?? 'reactant';
            if (!in_array($role, self::ROLES, true)) {
                throw new ImproperActionException('Invalid reaction component role.');
            }
            $keys[$key] = $role;
            $entry = array(
                'component_key' => $key,
                'role' => $role,
                'name' => self::text($component['name'] ?? '', 1024),
                'molfile' => self::text($component['molfile'] ?? '', 500_000),
                'smiles' => self::text($component['smiles'] ?? '', 60_000),
                'inchi_key' => self::text($component['inchi_key'] ?? '', 27),
                'molecular_formula' => self::text($component['molecular_formula'] ?? '', 255),
                'molecular_weight' => self::number($component['molecular_weight'] ?? null, 0, 999999999),
                'compound_id' => self::id($component['compound_id'] ?? null),
                'item_id' => self::id($component['item_id'] ?? null),
                'material_form' => $component['material_form'] ?? 'neat',
                'purity_percent' => self::number($component['purity_percent'] ?? 100, 0, 100),
                'density_g_ml' => self::number($component['density_g_ml'] ?? null),
                'concentration_mol_l' => self::number($component['concentration_mol_l'] ?? null),
                'coefficient' => self::number($component['coefficient'] ?? 1, 0.000001, 999999),
                'planned_basis' => $component['planned_basis'] ?? 'mass',
                'planned_value' => self::number($component['planned_value'] ?? null),
                'actual_basis' => $component['actual_basis'] ?? 'mass',
                'actual_value' => self::number($component['actual_value'] ?? null),
            );
            if (!in_array($entry['material_form'], array('neat', 'solution'), true)
                || !in_array($entry['planned_basis'], array('mass', 'volume', 'amount', 'equivalents'), true)
                || !in_array($entry['actual_basis'], array('mass', 'volume', 'amount'), true)) {
                throw new ImproperActionException('Invalid material form or quantity basis.');
            }
            if ($entry['coefficient'] === null || $entry['purity_percent'] === null) {
                throw new ImproperActionException('Coefficient and purity must be specified.');
            }
            $defaultUnits = array('mass' => 'g', 'volume' => 'mL', 'amount' => 'mol', 'equivalents' => 'eq');
            foreach (array('planned', 'actual') as $phase) {
                $unit = $component[$phase . '_unit'] ?? $defaultUnits[$entry[$phase . '_basis']];
                if (!is_string($unit) || !isset(self::UNITS[$unit]) || self::UNITS[$unit][0] !== $entry[$phase . '_basis']) {
                    throw new ImproperActionException('The quantity unit does not match its basis.');
                }
                $entry[$phase . '_unit'] = $unit;
            }
            $this->components[] = $entry;
        }
        foreach (array('reference_component_key', 'yield_basis_component_key') as $field) {
            $key = $this->reaction[$field];
            if ($key !== null && (!is_string($key) || !isset($keys[$key]) || !in_array($keys[$key], array('reactant', 'reagent'), true))) {
                throw new ImproperActionException('Reaction references must identify a reactant or reagent in this reaction.');
            }
        }
        foreach ($this->components as $component) {
            if ($component['planned_basis'] === 'equivalents'
                && ($component['component_key'] === $this->reaction['reference_component_key'] || $this->reaction['reference_component_key'] === null)) {
                throw new ImproperActionException('A reference with an absolute planned quantity is required to plan equivalents.');
            }
        }
    }

    private static function text(mixed $value, int $limit): string
    {
        if (!is_string($value) || strlen($value) > $limit || str_contains($value, "\0")) {
            throw new ImproperActionException('Invalid or oversized reaction text.');
        }
        return $value;
    }

    private static function number(mixed $value, float $min = 0, float $max = 999999999999): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ((!is_string($value) && !is_int($value) && !is_float($value))
            || !is_numeric($value) || !is_finite((float) $value) || (float) $value < $min || (float) $value > $max) {
            throw new ImproperActionException('Invalid reaction quantity.');
        }
        // Expand scientific notation without rounding entered decimal strings
        // through a binary float. The database supports 12 fractional places.
        if (strlen((string) $value) > 128 || preg_match('/^([+-]?)(\d*)(?:\.(\d*))?(?:[eE]([+-]?\d{1,3}))?$/D', (string) $value, $parts) !== 1) {
            throw new ImproperActionException('Invalid reaction quantity.');
        }
        $digits = $parts[2] . ($parts[3] ?? '');
        $point = strlen($parts[2]) + (int) ($parts[4] ?? 0);
        if ($point < 0) {
            $digits = str_repeat('0', -$point) . $digits;
            $point = 0;
        }
        $digits = str_pad($digits, $point, '0');
        $integer = ltrim(substr($digits, 0, $point), '0') ?: '0';
        $fraction = rtrim(substr($digits, $point), '0');
        if (strlen($fraction) > 12) {
            throw new ImproperActionException('Reaction quantities support at most 12 decimal places.');
        }
        return ($parts[1] === '-' && (float) $value !== 0.0 ? '-' : '') . $integer . ($fraction === '' ? '' : '.' . $fraction);
    }

    private static function id(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_int($value) && !is_string($value)) {
            throw new ImproperActionException('Invalid reaction compound or resource id.');
        }
        $id = filter_var($value, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'max_range' => 4294967295)));
        if ($id === false) {
            throw new ImproperActionException('Invalid reaction compound or resource id.');
        }
        return $id;
    }

    private static function preview(mixed $value): string
    {
        $value = self::text($value, 1_000_000);
        if ($value === '') {
            return '';
        }
        $bytes = base64_decode($value, true);
        $image = $bytes === false ? false : @getimagesizefromstring($bytes);
        if ($image === false || $image[2] !== IMAGETYPE_PNG || $image[0] > 4000 || $image[1] > 4000) {
            throw new ImproperActionException('Reaction previews must be PNG images no larger than 4000 pixels.');
        }
        return $value;
    }
}
