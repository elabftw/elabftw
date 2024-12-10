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
use Elabftw\Services\Check;
use Symfony\Component\HttpFoundation\Request;

/**
 * This class holds the values for limit, offset, order and sort
 */
class BaseQueryParams
{
    public Orderby $orderby = Orderby::Date;

    public Sort $sort = Sort::Desc;

    public int $limit = 15;

    public int $offset = 0;

    public bool $includeArchived = false;

    public function __construct(protected Request $Request)
    {
        // we don't care about the value, so it can be 'on' from a checkbox or 1 or anything really
        $this->includeArchived = $this->Request->query->has('archived');
        if ($this->Request->query->has('limit')) {
            $this->limit = Check::limit($this->Request->query->getInt('limit'));
        }
        if ($this->Request->query->has('offset') && Check::id($this->Request->query->getInt('offset')) !== false) {
            $this->offset = $this->Request->query->getInt('offset');
        }
        $this->sort = Sort::tryFrom($this->Request->query->getAlpha('sort')) ?? $this->sort;
        $this->orderby = Orderby::tryFrom($this->Request->query->getAlpha('order')) ?? $this->orderby;
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
