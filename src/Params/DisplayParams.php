<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Params;

use Elabftw\Elabftw\Tools;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\FilterableColumn;
use Elabftw\Enums\Orderby;
use Elabftw\Enums\Scope;
use Elabftw\Enums\Sort;
use Elabftw\Enums\State;
use Elabftw\Models\Tags2Entity;
use Elabftw\Models\Users;
use Elabftw\Services\Check;
use Override;
use Symfony\Component\HttpFoundation\InputBag;

use function explode;
use function sprintf;
use function trim;

/**
 * This class holds the values for limit, offset, order and sort
 * It is based on user preferences, overridden by request parameters
 */
final class DisplayParams extends BaseQueryParams
{
    public string $filterSql = '';

    // the search from the top right search bar on experiments/database
    public string $queryString = '';

    // the extended search query
    public string $extendedQuery = '';

    public ?EntityType $relatedOrigin = null;

    protected string $orderIsPinnedSql = 'is_pinned DESC,';

    public function __construct(
        private Users $requester,
        public EntityType $entityType,
        protected ?InputBag $query = null,
        public Orderby $orderby = Orderby::Lastchange,
        public Sort $sort = Sort::Desc,
        public int $limit = 15,
        public int $offset = 0,
        public array $states = array(State::Normal),
        public bool $skipOrderPinned = false,
    ) {
        // query parameters will override user defaults
        parent::__construct($query, $orderby, $sort, $limit, $offset, $states);
        if ($skipOrderPinned === true) {
            $this->orderIsPinnedSql = '';
        }
        $this->adjust();
    }

    public function appendFilterSql(FilterableColumn $column, int $value): void
    {
        $this->filterSql .= sprintf(' AND %s = %d', $column->value, $value);
    }

    #[Override]
    public function getSql(): string
    {
        return sprintf(
            'ORDER BY %s %s %s, entity.id %s LIMIT %d OFFSET %d',
            $this->orderIsPinnedSql,
            $this->orderby->toSql(),
            $this->sort->value,
            $this->sort->value,
            $this->limit,
            $this->offset,
        );
    }

    /**
     * Adjust the settings based on the Request
     */
    private function adjust(): void
    {
        $query = $this->getQuery();
        if (!empty($query->get('q'))) {
            $this->queryString = trim($query->getString('q'));
        }
        if (!empty($query->get('extended'))) {
            $this->extendedQuery = trim($query->getString('extended'));
        }

        // SCOPE FILTER
        // default scope is the user setting, but can be overridden by query param
        $scopeInt = $this->requester->userData['scope_' . $this->entityType->value] ?? Scope::Everything->value;
        if (Check::id($query->getInt('scope')) !== false) {
            $scopeInt = $query->getInt('scope');
        }
        $scope = Scope::from($scopeInt);

        // filter by user if we don't want to show the rest of the team
        // looking for an owner will bypass the user preference
        // same with an extended search: we show all
        if ($scope === Scope::User && empty($query->get('owner')) && empty($query->get('extended'))) {
            $this->appendFilterSql(FilterableColumn::Owner, $this->requester->userData['userid']);
        }
        // add filter on team only if scope is not set to everything
        if ($this->requester->userData['scope_' . $this->entityType->value] === Scope::Team && $scope !== Scope::Everything) {
            $this->appendFilterSql(FilterableColumn::Team, $this->requester->team ?? 0);
        }
        // TAGS SEARCH
        if (!empty(($query->all('tags'))[0])) {
            // get all the ids with that tag
            $tags = $query->all('tags');
            $Tags2Entity = new Tags2Entity($this->requester, $this->entityType);
            $this->filterSql = Tools::getIdFilterSql($Tags2Entity->getEntitiesIdFromTags('tag', $tags, $scope));
        }

        // RELATED FILTER
        if (Check::id($query->getInt('related')) !== false) {
            $this->appendFilterSql(FilterableColumn::Related, $query->getInt('related'));
            $this->relatedOrigin = EntityType::tryFrom($query->getAlpha('related_origin')) ?? $this->entityType;
        }

        // Note: we use getString() and not getInt() because values can be string separated (1,5)
        // CATEGORY FILTER
        // cat is for backward compatibility
        $this->filterSql .= $this->getSqlIn('entity.category', $query->getString('cat'));
        $this->filterSql .= $this->getSqlIn('entity.category', $query->getString('category'));
        // STATUS FILTER
        $this->filterSql .= $this->getSqlIn('entity.status', $query->getString('status'));
        // OWNER (USERID) FILTER
        $this->filterSql .= $this->getSqlIn('entity.userid', $query->getString('owner'));
        // LOCK FILTER: 0 or 1, use getInt()
        $this->filterSql .= $this->getSqlIn('entity.locked', $query->getString('locked'));
        // TIMESTAMPED FILTER, same as lock
        $this->filterSql .= $this->getSqlIn('entity.timestamped', $query->getString('timestamped'));
        // RATING FILTER
        $this->filterSql .= $this->getSqlIn('entity.rating', $query->getString('rating'));
    }

    /**
     * Create an SQL string to add a filter from a comma separated list of int
     * possibly including null value. Ugly but works.
     */
    private function getSqlIn(string $column, string|int $input): string
    {
        $input = (string) $input;
        if (empty($input)) {
            return '';
        }
        $exploded = explode(',', $input);
        $numbers = array();
        $sql = ' AND';
        foreach ($exploded as $value) {
            // we need to treat null specially, it cannot be part of the IN()
            if (strtolower($value) === 'null') {
                $sql = sprintf(' AND %s IS NULL OR', $column);
                continue;
            }
            $numbers[] = (int) $value;
        }
        if ($numbers) {
            return $sql . sprintf(' %s IN (%s)', $column, implode(', ', $numbers));
        }
        return rtrim($sql, ' OR');
    }
}
