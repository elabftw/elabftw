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

    private const string PUG_URL = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug';

    private const string PUG_VIEW_URL = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug_view/data';

    public function __construct(private HttpGetter $httpGetter) {}

    // not used
    public function fromPug(int $cid): Compound
    {
        usleep(self::REQ_DELAY);
        return Compound::fromPug($this->httpGetter->get(sprintf('%s/compound/cid/%d/json', self::PUG_URL, $cid)));
    }

    public function getCidFromCas(string $cas): int
    {
        usleep(self::REQ_DELAY);
        $json = $this->httpGetter->get(sprintf('%s/compound/xref/rn/%s/json', self::PUG_URL, $cas));
        $decoded = json_decode($json, true, 42);
        return $decoded['PC_Compounds'][0]['id']['id']['cid'];
    }

    public function fromPugView(int $cid): Compound
    {
        usleep(self::REQ_DELAY);
        return Compound::fromPugView($this->httpGetter->get(sprintf('%s/compound/%d/json', self::PUG_VIEW_URL, $cid)));
    }
}
