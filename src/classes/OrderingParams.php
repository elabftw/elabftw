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

use Elabftw\Enums\Orderable;
use Elabftw\Exceptions\ImproperActionException;

/**
 * Parameters passed for ordering stuff
 */
class OrderingParams
{
    public readonly Orderable $table;

    public readonly array $ordering;

    public function __construct(protected array $reqBody)
    {
        $this->table = Orderable::tryFrom($this->reqBody['table'] ?? '') ?? throw new ImproperActionException('Incorrect table');
        $this->ordering = $this->cleanup($this->reqBody['ordering']);
    }

    /**
     * Transform example_33 in 33
     */
    protected function cleanup(array $ordering): array
    {
        return array_map(function ($el) {
            return (int) explode('_', $el)[1];
        }, $ordering);
    }
}
