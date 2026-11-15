<?php

/**
 * @copyright 2026 Nicolas CARPi
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function hash;
use function implode;
use function in_array;
use function preg_split;
use function sort;
use function sprintf;
use function str_contains;
use function trim;

/**
 * Derive the searchable identity of a reaction from its validated components.
 * Both values are recomputed on every save, so they never drift from the components.
 */
final class ReactionIdentity
{
    private const array AGENTS = array('reagent', 'catalyst', 'solvent');

    /** Reaction SMILES (reactants>agents>products), in component order. Components without SMILES are left out. */
    public static function smiles(array $components): string
    {
        return sprintf(
            '%s>%s>%s',
            self::joinSmiles($components, array('reactant')),
            self::joinSmiles($components, self::AGENTS),
            self::joinSmiles($components, array('product')),
        );
    }

    /**
     * Same transformation, whatever the conditions: agents are ignored, and so is how
     * many times a molecule was drawn. Without an InChIKey on every reactant and product,
     * the identity is unknown rather than partial, to avoid false matches.
     */
    public static function key(array $components): ?string
    {
        $sides = array();
        foreach (array('reactant', 'product') as $role) {
            $side = array_values(array_filter($components, static fn(array $component): bool => $component['role'] === $role));
            $keys = array_map(static fn(array $component): string => trim((string) $component['inchi_key']), $side);
            if ($keys === array() || in_array('', $keys, true)) {
                return null;
            }
            $keys = array_unique($keys);
            sort($keys);
            $sides[] = implode('.', $keys);
        }
        return hash('sha256', implode('>>', $sides));
    }

    /** A SMILES ends at the first whitespace: anything after it is a title or a CXSMILES extension */
    public static function normalizeSmiles(string $smiles): string
    {
        return (preg_split('/\s/', trim($smiles)) ?: array(''))[0];
    }

    private static function joinSmiles(array $components, array $roles): string
    {
        $smiles = array();
        foreach ($components as $component) {
            $value = self::normalizeSmiles((string) $component['smiles']);
            // a '>' would break the reaction SMILES structure
            if ($value !== '' && !str_contains($value, '>') && in_array($component['role'], $roles, true)) {
                $smiles[] = $value;
            }
        }
        return implode('.', $smiles);
    }
}
