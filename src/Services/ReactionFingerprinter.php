<?php

/**
 * @copyright 2026 Nicolas CARPi
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\FingerprinterInterface;
use Elabftw\Models\FingerprintTable;
use Symfony\Component\Process\Exception\RuntimeException as ProcessException;

use function array_key_exists;
use function array_sum;
use function count;
use function is_array;

/**
 * Fingerprint reaction components for substructure search.
 * The fingerprints are a search index: a structure that cannot be fingerprinted
 * (generic group, unusual notation, fingerprinter unavailable) is still saved,
 * it is only absent from substructure results.
 */
final class ReactionFingerprinter
{
    public function __construct(private FingerprinterInterface $fingerprinter) {}

    /**
     * @return array<string, list<int>> 32 integers per component_key
     */
    public function calculate(array $components): array
    {
        $bySmiles = array();
        $result = array();
        foreach ($components as $component) {
            $smiles = ReactionIdentity::normalizeSmiles((string) $component['smiles']);
            if ($smiles === '') {
                continue;
            }
            // the same molecule is often drawn several times: only run the fingerprinter once, even when it fails
            if (!array_key_exists($smiles, $bySmiles)) {
                $bySmiles[$smiles] = $this->fingerprint($smiles);
            }
            if ($bySmiles[$smiles] !== null) {
                $result[(string) $component['component_key']] = $bySmiles[$smiles];
            }
        }
        return $result;
    }

    /**
     * @return ?list<int>
     */
    public function fingerprint(string $smiles): ?array
    {
        try {
            $fp = $this->fingerprinter->calculate('smi', $smiles);
        } catch (ImproperActionException | ProcessException) {
            return null;
        }
        $data = $fp['data'] ?? null;
        if (!is_array($data) || count($data) !== FingerprintTable::WORDS || array_sum($data) === 0) {
            return null;
        }
        $ints = array();
        foreach ($data as $value) {
            $ints[] = (int) $value;
        }
        return $ints;
    }
}
