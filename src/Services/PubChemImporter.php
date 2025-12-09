<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\Compound;
use JsonException;

use function usleep;
use function rawurlencode;

/**
 * Import a compound from PubChem
 */
final class PubChemImporter
{
    // 300 ms delay before requests, to avoid abuse
    private const int REQ_DELAY = 300000;

    public function __construct(
        private HttpGetter $httpGetter,
        private readonly string $pugUrl,
        private readonly string $pugViewUrl,
    ) {}

    // not used
    public function fromPug(int $cid): Compound
    {
        usleep(self::REQ_DELAY);
        return Compound::fromPug($this->httpGetter->get(sprintf('%s/compound/cid/%d/json', $this->pugUrl, $cid))->getBody()->getContents());
    }

    public function getCidFromCas(string $cas): array
    {
        usleep(self::REQ_DELAY);
        $json = $this->httpGetter->get(sprintf('%s/compound/xref/RegistryID/%s/cids/json', $this->pugUrl, rawurlencode($cas)))->getBody()->getContents();
        return $this->decodeCidList($json);
    }

    public function getCidFromName(string $name): array
    {
        usleep(self::REQ_DELAY);
        $json = $this->httpGetter->get(sprintf('%s/compound/name/%s/cids/json', $this->pugUrl, rawurlencode($name)))->getBody()->getContents();
        return $this->decodeCidList($json);
    }

    public function fromPugView(int $cid): Compound
    {
        usleep(self::REQ_DELAY);
        return Compound::fromPugView($this->httpGetter->get(sprintf('%s/compound/%d/json', $this->pugViewUrl, $cid))->getBody()->getContents());
    }

    private function decodeCidList(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 10, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return array();
        }
        return $decoded['IdentifierList']['CID'] ?? array();
    }
}
