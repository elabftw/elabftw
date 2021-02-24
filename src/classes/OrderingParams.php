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
    /** @var string $table */
    private $table;

    /** @var array $ordering */
    private $ordering;

    /**
     * Constructor
     *
     * @param string $table
     * @param array<mixed> $ordering
     */
    public function __construct(string $table, array $ordering)
    {
        $this->table = $table;
        $this->ordering = $ordering;
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
