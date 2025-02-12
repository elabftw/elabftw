<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha CAMARA <mouss@deltablot.email>
 * @copyright 2012, 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Params;

use Elabftw\Interfaces\ContentParamsInterface;

class MetadataParams extends ContentParams implements ContentParamsInterface
{
    public function getContent(): string
    {
        return $this->content;
    }
}
