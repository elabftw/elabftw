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
        return Compound::fromPug($this->httpGetter->get(sprintf('%s/compound/cid/%d/json', $this->pugUrl, $cid)));
    }

    public function getCidFromCas(string $cas): array
    {
        usleep(self::REQ_DELAY);
        $json = $this->httpGetter->get(sprintf('%s/compound/xref/RegistryID/%s/cids/json', $this->pugUrl, rawurlencode($cas)));
        $decoded = json_decode($json, true, 10, JSON_THROW_ON_ERROR);
        return $decoded['IdentifierList']['CID'] ?? array();
    }

    public function getCidFromName(string $name): array
    {
        usleep(self::REQ_DELAY);
        $json = $this->httpGetter->get(sprintf('%s/compound/name/%s/cids/json', $this->pugUrl, rawurlencode($name)));
        $decoded = json_decode($json, true, 10, JSON_THROW_ON_ERROR);
        return $decoded['IdentifierList']['CID'] ?? array();
    }

    public function fromPugView(int $cid): Compound
    {
        usleep(self::REQ_DELAY);
        return Compound::fromPugView($this->httpGetter->get(sprintf('%s/compound/%d/json', $this->pugViewUrl, $cid)));
    }
}
