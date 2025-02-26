<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Params;

use Elabftw\Enums\Orderby;
use Elabftw\Enums\Sort;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Services\Check;
use Symfony\Component\HttpFoundation\InputBag;
use Override;

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
        public array $states = array(State::Normal),
    ) {
        if ($query !== null) {
            if ($query->has('limit')) {
                $this->limit = Check::limit($query->getInt('limit'));
            }
            if ($query->has('offset') && Check::id($query->getInt('offset')) !== false) {
                $this->offset = $query->getInt('offset');
            }
            $this->sort = Sort::tryFrom($query->getAlpha('sort')) ?? $this->sort;
            $this->orderby = Orderby::tryFrom($query->getAlpha('order')) ?? $this->orderby;

            // STATE
            // example: ?state=1,2 to include normal and archived
            if ($query->get('state')) {
                $states = array();
                foreach (explode(',', $query->getString('state')) as $state) {
                    $states[] = State::tryFrom((int) $state) ?? throw new ImproperActionException(sprintf('Invalid state parameter. Must be one of: %s', State::toCsListVerbose()));
                }
                $this->states = $states;
            }
        }
    }

    #[Override]
    public function getQuery(): InputBag
    {
        return $this->query ?? new InputBag();
    }

    #[Override]
    public function getLimit(): int
    {
        return $this->limit;
    }

    #[Override]
    public function getSql(): string
    {
        return sprintf(
            '%s ORDER BY %s %s LIMIT %d OFFSET %d',
            $this->getStatesSql('entity'),
            $this->orderby->toSql(),
            $this->sort->value,
            $this->limit,
            $this->offset,
        );
    }

    public function getStatesSql(string $tableName): string
    {
        return sprintf(' AND %s.state IN (%s)', $tableName, implode(', ', array_map(fn($state) => $state->value, $this->states)));
    }
}
