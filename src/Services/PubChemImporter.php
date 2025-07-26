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
        $json = $this->httpGetter->get(sprintf('%s/compound/xref/rn/%s/cids/json', $this->pugUrl, $cas));
        $decoded = json_decode($json, true, 10);
        return $decoded['IdentifierList']['CID'];
    }

    public function getCidFromName(string $name): array
    {
        usleep(self::REQ_DELAY);
        $json = $this->httpGetter->get(sprintf('%s/compound/name/%s/cids/json', $this->pugUrl, $name));
        $decoded = json_decode($json, true, 10);
        return $decoded['IdentifierList']['CID'];
    }

    public function fromPugView(int $cid): Compound
    {
        usleep(self::REQ_DELAY);
        return Compound::fromPugView($this->httpGetter->get(sprintf('%s/compound/%d/json', $this->pugViewUrl, $cid)));
    }
}
