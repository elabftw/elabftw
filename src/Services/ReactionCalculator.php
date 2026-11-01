<?php

/**
 * @copyright 2026 Nicolas CARPi
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Params\ReactionParams;

use function array_column;

/**
 * Derive quantities without rewriting entered measurements. Internal units are
 * g, mL, mol, g/mol, g/mL and mol/L. Missing inputs stay unknown, including yield.
 */
final class ReactionCalculator
{
    public function calculate(array $reaction, array $components): array
    {
        $indexed = array_column($components, null, 'component_key');
        foreach (array('planned', 'actual') as $phase) {
            $reference = $indexed[$reaction['reference_component_key'] ?? ''] ?? null;
            $referenceAmount = $reference === null ? null : $this->amount($reference, $phase);
            $amounts = array();
            foreach ($components as $component) {
                $amounts[$component['component_key']] = $component[$phase . '_basis'] === 'equivalents'
                    ? ($referenceAmount === null || $component[$phase . '_value'] === null ? null : $referenceAmount * (float) $component[$phase . '_value'])
                    : $this->amount($component, $phase);
            }
            $basisKey = $reaction['yield_basis_component_key'] ?? '';
            $basis = $indexed[$basisKey] ?? null;
            $basisAmount = $amounts[$basisKey] ?? null;
            foreach ($components as &$component) {
                $amount = $amounts[$component['component_key']];
                $mass = $component[$phase . '_basis'] === 'mass' ? $this->value($component, $phase) : null;
                $volume = $component[$phase . '_basis'] === 'volume' ? $this->value($component, $phase) : null;
                $density = (float) $component['density_g_ml'];
                // Density conversions do not require a known molecular structure.
                if ($volume === null && $mass !== null && $density > 0) {
                    $volume = $mass / $density;
                }
                if ($component['material_form'] === 'solution') {
                    if ($volume === null && $amount !== null && (float) $component['concentration_mol_l'] > 0) {
                        $volume = $amount / (float) $component['concentration_mol_l'] * 1000.0;
                    }
                } elseif ($mass === null && $amount !== null && (float) $component['molecular_weight'] > 0 && (float) $component['purity_percent'] > 0) {
                    $mass = $amount * (float) $component['molecular_weight'] / ((float) $component['purity_percent'] / 100.0);
                }
                if ($mass === null && $volume !== null && $density > 0) {
                    $mass = $volume * $density;
                }
                if ($volume === null && $mass !== null && $density > 0) {
                    $volume = $mass / $density;
                }
                $theoretical = $component['role'] === 'product' && $basis !== null && $basisAmount !== null && $basisAmount > 0
                    ? $basisAmount / (float) $basis['coefficient'] * (float) $component['coefficient'] : null;
                $component[$phase] = array(
                    'amount_mol' => $amount,
                    'mass_g' => $mass,
                    'volume_ml' => $volume,
                    'equivalents' => $amount === null || $referenceAmount === null || $referenceAmount <= 0 ? null : $amount / $referenceAmount,
                    'theoretical_amount_mol' => $theoretical,
                    'yield_percent' => $theoretical === null || $amount === null ? null : $amount / $theoretical * 100.0,
                );
            }
            unset($component);
        }
        return $components;
    }

    private function amount(array $component, string $phase): ?float
    {
        $value = $this->value($component, $phase);
        if ($value === null) {
            return null;
        }
        $weight = (float) $component['molecular_weight'];
        $purity = (float) $component['purity_percent'] / 100.0;
        return match ($component[$phase . '_basis']) {
            'amount' => $value,
            'mass' => $component['material_form'] === 'solution'
                ? ((float) $component['density_g_ml'] > 0 && $component['concentration_mol_l'] !== null
                    ? $value / (float) $component['density_g_ml'] / 1000.0 * (float) $component['concentration_mol_l'] : null)
                : ($weight > 0 ? $value * $purity / $weight : null),
            'volume' => $component['material_form'] === 'solution'
                // Stock molarity already describes the concentration of the solute.
                ? ($component['concentration_mol_l'] !== null ? $value / 1000.0 * (float) $component['concentration_mol_l'] : null)
                : ($weight > 0 && (float) $component['density_g_ml'] > 0 ? $value * (float) $component['density_g_ml'] * $purity / $weight : null),
            default => null,
        };
    }

    private function value(array $component, string $phase): ?float
    {
        return $component[$phase . '_value'] === null ? null : (float) $component[$phase . '_value'] * ReactionParams::UNITS[$component[$phase . '_unit']][1];
    }
}
