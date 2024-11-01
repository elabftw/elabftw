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
use Elabftw\Services\Check;
use Symfony\Component\HttpFoundation\InputBag;
use ValueError;

/**
 * This class holds the values for limit, offset, order and sort
 */
class BaseQueryParams implements QueryParamsInterface
{
    public function __construct(
        protected ?InputBag $query = null,
        public Orderby $orderby = Orderby::Date,
        public Sort $sort = Sort::Desc,
        public int $limit = 15,
        public int $offset = 0,
        public bool $includeArchived = false,
    ) {
        if ($query !== null) {
            // we don't care about the value, so it can be 'on' from a checkbox or 1 or anything really
            $this->includeArchived = $query->has('archived');
            if ($query->has('limit')) {
                $this->limit = Check::limit($query->getInt('limit'));
            }
            if ($query->has('offset') && Check::id($query->getInt('offset')) !== false) {
                $this->offset = $query->getInt('offset');
            }
            $this->sort = Sort::tryFrom($query->getAlpha('sort')) ?? $this->sort;
            $this->orderby = Orderby::tryFrom($query->getAlpha('order')) ?? $this->orderby;
        }
    }

    public function getQuery(): InputBag
    {
        return $this->query ?? throw new ValueError('Query is null here.');
    }

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
