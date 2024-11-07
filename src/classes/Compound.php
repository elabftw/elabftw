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

/**
 * Representation of a chemical compound
 */
class Compound
{
    public function __construct(
        public ?string $cas = null,
        public ?int $cid = null,
        public ?string $inChI = null,
        public ?string $inChIKey = null,
        public ?int $isPublic = 1,
        public ?string $iupacName = null,
        public ?string $molecularFormula = null,
        public ?string $name = null,
        public ?array $safetyIcons = array(),
        public ?string $smiles = null,
    ) {}

    public function toArray(): array
    {
        return array(
            'cas' => $this->cas,
            'cid' => $this->cid,
            'inChI' => $this->inChI,
            'inChIKey' => $this->inChIKey,
            'isPublic' => $this->isPublic,
            'iupacName' => $this->iupacName,
            'molecularFormula' => $this->molecularFormula,
            'name' => $this->name,
            'safetyIcons' => $this->safetyIcons,
            'smiles' => $this->smiles,
        );
    }

    public static function fromPugView(string $json): self
    {
        $all = json_decode($json, true, 42)['Record'];
        $compound = new self();

        foreach ($all['Section'] as $section) {
            if ($section['TOCHeading'] === 'Chemical Safety') {
                foreach ($section['Information'] as $subSection) {
                    if ($subSection['Name'] === 'Chemical Safety') {
                        foreach ($subSection['Value'][0]['Markup'] as $ghs) {
                            $compound->safetyIcons[] = array(
                                'src' => self::getLastPartOfUrl($ghs['URL']),
                                'alt' => $ghs['Extra'],
                            );
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

    private static function getLastPartOfUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path)) {
            return '';
        }
        return basename(rtrim($path, '/'));
    }
}
