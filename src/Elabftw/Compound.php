<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Enums\GhsSignalWord;

use function array_column;
use function array_merge;
use function array_unique;
use function array_values;
use function is_array;
use function is_string;
use function json_decode;
use function preg_match;
use function preg_match_all;
use function trim;

/**
 * Representation of a chemical compound
 */
final class Compound
{
    // propertyID of the GHS data of a compound in an .eln file
    public const string ELN_GHS_SIGNAL_WORD = 'GHS signal word';

    public const string ELN_GHS_HAZARD_STATEMENT = 'GHS hazard statement';

    public const string ELN_GHS_PRECAUTIONARY_STATEMENT = 'GHS precautionary statement';

    // H300, H360FD, EUH066, P301+P330+P331, P370+P380+P375[+P378]: what PubChem, CLP and GHS write
    public const string GHS_CODE_REGEX = '(?:EUH|H|P)\d{3}[A-Za-z]*(?:\+(?:EUH|H|P)\d{3}[A-Za-z]*)*(?:\[\+P\d{3}\])?';

    /**
     * @param string[] $ghsStatements codes of the GHS hazard and precautionary statements
     */
    public function __construct(
        public ?string $cas = null,
        public ?int $cid = null,
        public ?string $inChI = null,
        public ?string $inChIKey = null,
        public ?int $isPublic = 1,
        public ?string $iupacName = null,
        public ?string $molecularFormula = null,
        public ?float $molecularWeight = null,
        public ?string $name = null,
        public ?string $smiles = null,
        public bool $isCorrosive = false,
        public bool $isExplosive = false,
        public bool $isFlammable = false,
        public bool $isGasUnderPressure = false,
        public bool $isHazardous2env = false,
        public bool $isHazardous2health = false,
        public bool $isSeriousHealthHazard = false,
        public bool $isOxidising = false,
        public bool $isToxic = false,
        public ?GhsSignalWord $ghsSignalWord = null,
        public array $ghsStatements = array(),
    ) {}

    /**
     * The GHS statement codes found in a text, like a cell of a CSV file: "H225, H319 P210;P233"
     *
     * @return string[]
     */
    public static function extractGhsCodes(string $text): array
    {
        preg_match_all('/(?<![A-Za-z0-9])' . self::GHS_CODE_REGEX . '/', $text, $matches);
        return array_values(array_unique($matches[0]));
    }

    public function toArray(): array
    {
        return array(
            'cas' => $this->cas,
            'cid' => $this->cid,
            'inChI' => $this->inChI,
            'inChIKey' => $this->inChIKey,
            'isPublic' => $this->isPublic,
            'iupacName' => $this->iupacName ?? $this->name,
            'molecularFormula' => $this->molecularFormula,
            'molecularWeight' => $this->molecularWeight,
            'name' => $this->name ?? $this->iupacName,
            'smiles' => $this->smiles,
            'ghsSignalWord' => $this->ghsSignalWord?->value,
            'ghsStatements' => $this->ghsStatements,
        );
    }

    public static function fromPugView(string $json): self
    {
        $all = json_decode($json, true, 42)['Record'];
        $compound = new self();

        foreach ($all['Section'] as $section) {
            // Grab the GHS pictograms, signal word and statements
            if ($section['TOCHeading'] === 'Safety and Hazards') {
                foreach ($section['Section'] ?? array() as $subSection) {
                    if ($subSection['TOCHeading'] !== 'Hazards Identification') {
                        continue;
                    }
                    foreach ($subSection['Section'] ?? array() as $subSubSection) {
                        if ($subSubSection['TOCHeading'] === 'GHS Classification') {
                            $compound->addGhsClassification($subSubSection['Information'] ?? array());
                        }
                    }
                }
            }

            // Molecular Weight
            if ($section['TOCHeading'] === 'Chemical and Physical Properties') {
                foreach ($section['Section'] as $subSection) {
                    if ($subSection['TOCHeading'] === 'Computed Properties') {
                        foreach ($subSection['Section'] as $subSubSection) {
                            if ($subSubSection['TOCHeading'] === 'Molecular Weight') {
                                $compound->molecularWeight = (float) $subSubSection['Information'][0]['Value']['StringWithMarkup'][0]['String'];
                            }
                        }
                    }
                }
            }

            if ($section['TOCHeading'] === 'Names and Identifiers') {
                foreach ($section['Section'] as $subSection) {
                    if ($subSection['TOCHeading'] === 'Computed Descriptors') {
                        foreach ($subSection['Section'] as $subSubSection) {
                            if ($subSubSection['TOCHeading'] === 'IUPAC Name') {
                                $compound->iupacName = $subSubSection['Information'][0]['Value']['StringWithMarkup'][0]['String'];
                            }
                            if ($subSubSection['TOCHeading'] === 'InChI') {
                                $compound->inChI = $subSubSection['Information'][0]['Value']['StringWithMarkup'][0]['String'];
                            }
                            if ($subSubSection['TOCHeading'] === 'InChIKey') {
                                $compound->inChIKey = $subSubSection['Information'][0]['Value']['StringWithMarkup'][0]['String'];
                            }
                            if ($subSubSection['TOCHeading'] === 'SMILES') {
                                $compound->smiles = $subSubSection['Information'][0]['Value']['StringWithMarkup'][0]['String'];
                            }
                        }
                    }
                    if ($subSection['TOCHeading'] === 'Molecular Formula') {
                        $compound->molecularFormula = $subSection['Information'][0]['Value']['StringWithMarkup'][0]['String'];
                    }
                    if ($subSection['TOCHeading'] === 'Other Identifiers') {
                        foreach ($subSection['Section'] as $subSubSection) {
                            if ($subSubSection['TOCHeading'] === 'CAS') {
                                $compound->cas = $subSubSection['Information'][0]['Value']['StringWithMarkup'][0]['String'];
                            }
                        }
                    }
                }
            }
        }
        $compound->cid = $all['RecordNumber'];
        $compound->name = $all['RecordTitle'];
        return $compound;
    }

    // not used
    public static function fromPug(string $json): self
    {
        $all = json_decode($json, true, 42)['PC_Compounds'][0];
        $smiles = $iupacName = $inChI = $inChIKey = $molecularFormula = null;
        foreach ($all['props'] as $prop) {
            if ($prop['urn']['label'] === 'SMILES' && $prop['urn']['name'] === 'Canonical') {
                $smiles = $prop['value']['sval'];
            }
            if ($prop['urn']['label'] === 'IUPAC Name' && $prop['urn']['name'] === 'Traditional') {
                $iupacName = $prop['value']['sval'];
            }
            if ($prop['urn']['label'] === 'InChI' && $prop['urn']['name'] === 'Standard') {
                $inChI = $prop['value']['sval'];
            }
            if ($prop['urn']['label'] === 'InChIKey' && $prop['urn']['name'] === 'Standard') {
                $inChIKey = $prop['value']['sval'];
            }
            if ($prop['urn']['label'] === 'Molecular Formula') {
                $molecularFormula = $prop['value']['sval'];
            }
        }
        $cid = $all['id']['id']['cid'];
        return new self(
            cid: $cid,
            inChI: $inChI,
            inChIKey: $inChIKey,
            iupacName: $iupacName,
            molecularFormula: $molecularFormula,
            name: 'CID' . $cid,
            smiles: $smiles,
        );
    }

    /**
     * PubChem lists one classification per source (ECHA C&L notifications, national lists...):
     * everything is kept, so users can remove what does not apply to their product
     */
    private function addGhsClassification(array $information): void
    {
        $codes = array();
        foreach ($information as $info) {
            $strings = array_column($info['Value']['StringWithMarkup'] ?? array(), 'String');
            switch ($info['Name'] ?? '') {
                case 'Pictogram(s)':
                    $this->addPictograms($info);
                    break;
                case 'Signal':
                    foreach ($strings as $string) {
                        $this->ghsSignalWord = GhsSignalWord::mostSevere($this->ghsSignalWord, GhsSignalWord::tryFrom(trim((string) $string)));
                    }
                    break;
                    // one statement per string: "H300 (98.7%): Fatal if swallowed [Danger Acute toxicity, oral]"
                case 'GHS Hazard Statements':
                    foreach ($strings as $string) {
                        if (is_string($string) && preg_match('/^' . self::GHS_CODE_REGEX . '/', trim($string), $matches) === 1) {
                            $codes[] = $matches[0];
                        }
                    }
                    break;
                    // all the codes in one string: "P203, P233, P260, ..., P301+P316, ..."
                case 'Precautionary Statement Codes':
                    foreach ($strings as $string) {
                        $codes = array_merge($codes, self::extractGhsCodes((string) $string));
                    }
                    break;
                default:
            }
        }
        $this->ghsStatements = array_values(array_unique(array_merge($this->ghsStatements, $codes)));
    }

    private function addPictograms(array $info): void
    {
        $markups = $info['Value']['StringWithMarkup'][0]['Markup'] ?? $info['Value']['StringWithMarkup'] ?? array();
        foreach ($markups as $ghs) {
            $extra = is_array($ghs) ? ($ghs['Extra'] ?? null) : $ghs;
            match ($extra) {
                'Corrosive' => $this->isCorrosive = true,
                'Explosive' => $this->isExplosive = true,
                'Flammable' => $this->isFlammable = true,
                'Compressed Gas' => $this->isGasUnderPressure = true,
                'Environmental Hazard' => $this->isHazardous2env = true,
                'Irritant' => $this->isHazardous2health = true,
                'Health Hazard' => $this->isSeriousHealthHazard = true,
                'Oxidizer' => $this->isOxidising = true,
                'Acute Toxic' => $this->isToxic = true,
                default => null,
            };
        }
    }
}
