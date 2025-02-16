<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Params;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\Filter;

use function array_map;
use function str_split;
use function trim;

final class CompoundParams extends ContentParams
{
    public function getContent(): string | int | float
    {
        return match ($this->target) {
            'name',
            'chebi_id',
            'chembl_id',
            'dea_number',
            'drugbank_id',
            'dsstox_id',
            'hmdb_id',
            'inchi',
            'inchi_key',
            'iupac_name',
            'kegg_id',
            'metabolomics_wb_id',
            'molecular_formula',
            'nci_code',
            'nikkaji_number',
            'pharmgkb_id',
            'pharos_ligand_id',
            'pubchem_cid',
            'rxcui',
            'smiles',
            'unii',
            'wikidata',
            'wikipedia' => trim($this->asString()),
            'ec_number' => $this->isValidEcOrExplode($this->asString()),
            'cas_number' => $this->isValidCasOrExplode($this->asString()),
            'is_corrosive',
            'is_explosive',
            'is_flammable',
            'is_gas_under_pressure',
            'is_hazardous2env',
            'is_hazardous2health',
            'is_oxidising',
            'is_toxic',
            'is_radioactive',
            'is_antibiotic_precursor',
            'is_drug_precursor',
            'is_explosive_precursor',
            'is_cmr',
            'is_nano',
            'is_controlled' => Filter::onToBinary($this->content),
            'molecular_weight' => (float) $this->content,
            'state' => $this->getState(),
            default => throw new ImproperActionException('Invalid target for compound update.'),
        };
    }

    private function isValidEcOrExplode(string $ecNumber): string
    {
        if (!$this->isValidEc($ecNumber)) {
            throw new ImproperActionException('Invalid EC number format!');
        }
        return $ecNumber;
    }

    private function isValidCasOrExplode(string $casNumber): string
    {
        if (!$this->isValidCas($casNumber)) {
            throw new ImproperActionException('Invalid CAS number format!');
        }
        return $casNumber;
    }

    /**
     * Ensure an EC number is correct
     * See: https://en.wikipedia.org/wiki/European_Community_number
     */
    private function isValidEc(string $input): bool
    {
        if (!preg_match('/^\d{3}-\d{3}-\d$/', $input)) {
            return false;
        }

        [$body, $middle, $checksum] = explode('-', $input);
        $fullNumber = $body . $middle;
        $digits = array_map('intval', str_split($fullNumber));

        $calculatedChecksum = 0;
        $weight = 1;

        foreach ($digits as $digit) {
            $calculatedChecksum += $digit * $weight;
            $weight++;
        }

        $calculatedChecksum %= 11;

        return (int) $checksum === $calculatedChecksum;
    }

    /**
     * Ensure a CAS number is correct
     * See: https://www.cas.org/training/documentation/chemical-substances/checkdig
     */
    private function isValidCas(string $input): bool
    {
        // first check the string format
        if (!preg_match('/^\d{2,7}-\d{2}-\d$/', $input)) {
            return false;
        }

        [$body, $middle, $checksum] = explode('-', $input);
        $fullNumber = $body . $middle;
        $digits = array_map('intval', str_split($fullNumber));

        $calculatedChecksum = 0;
        $weight = count($digits);

        foreach ($digits as $digit) {
            $calculatedChecksum += $digit * $weight;
            $weight--;
        }

        $calculatedChecksum %= 10;

        return (int) $checksum === $calculatedChecksum;
    }
}
