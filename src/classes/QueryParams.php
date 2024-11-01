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

use Elabftw\Enums\Orderby;
use Elabftw\Enums\Sort;
use Elabftw\Interfaces\QueryParamsInterface;

/**
 * This class holds the values for limit, offset, order and sort
 */
class QueryParams implements QueryParamsInterface
{
    public Orderby $orderby = Orderby::Date;

    public Sort $sort = Sort::Desc;

    public int $limit = 15;

    public int $offset = 0;
    //public bool $includeArchived = false;

    public function getSql(): string
    {
        return sprintf(
            ' ORDER BY %s %s LIMIT %d OFFSET %d',
            $this->orderby->toSql(),
            $this->sort->value,
            $this->limit,
            $this->offset,
        );
    }
}
