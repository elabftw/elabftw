<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

/**
 * Parameters passed for ordering stuff
 *
 */
class OrderingParams
{
    public function __construct(private string $table, private array $ordering)
    {
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getOrdering(): iterable
    {
        return $this->ordering;
    }
}
