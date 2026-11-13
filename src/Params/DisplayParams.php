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

use Elabftw\Elabftw\CompoundPermissions;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\FilterableColumn;
use Elabftw\Enums\Orderby;
use Elabftw\Enums\Scope;
use Elabftw\Enums\Sort;
use Elabftw\Enums\State;
use Elabftw\Models\Tags2Entity;
use Elabftw\Models\Users\Users;
use Elabftw\Services\Check;
use Elabftw\Services\ReactionSearch;
use Override;
use PDOStatement;
use Symfony\Component\HttpFoundation\InputBag;

use function explode;
use function str_starts_with;
use function sprintf;
use function trim;
use function implode;
use function strtolower;
use function array_unique;

/**
 * This class holds the values for limit, offset, order and sort
 * It is based on user preferences, overridden by request parameters
 */
final class DisplayParams extends BaseQueryParams
{
    public string $filterSql = '';

    // the search from the top right search bar on experiments/database
    public string $queryString = '';

    // fastq is the quick query string that we use in autocomplete for instance
    public string $fastq = '';

    // the extended search query
    public string $extendedQuery = '';

    public ?EntityType $relatedOrigin = null;

    protected string $orderIsPinnedSql = 'is_pinned DESC,';

    // the filters only on the columns of the entry table: what a query without the joins of the listing can apply, see getReactionSearch()
    private string $entityFilterSql = '';

    private ?ReactionSearch $ReactionSearch = null;

    public function __construct(
        private Users $requester,
        public EntityType $entityType,
        protected ?InputBag $query = null,
        public Orderby $orderby = Orderby::Lastchange,
        public Sort $sort = Sort::Desc,
        public int $limit = 0,
        public int $offset = 0,
        public array $states = array(State::Normal),
        public bool $skipOrderPinned = false,
    ) {
        // query parameters will override user defaults
        parent::__construct($query, $orderby, $sort, $limit, $offset, $states);
        $this->adjust();
    }

    public function appendFilterSql(FilterableColumn $column, int $value): void
    {
        $sql = sprintf(' AND %s = %d', $column->value, $value);
        $this->filterSql .= $sql;
        if (str_starts_with($column->value, 'entity.')) {
            $this->entityFilterSql .= $sql;
        }
    }

    #[Override]
    public function getSkipOrderPinned(): bool
    {
        return $this->skipOrderPinned;
    }

    #[Override]
    public function getSql(): string
    {
        if ($this->skipOrderPinned === true) {
            $this->orderIsPinnedSql = '';
        }
        $sql = sprintf(
            'ORDER BY %s %s %s, entity.id %s',
            $this->orderIsPinnedSql,
            $this->orderby->toSql(),
            $this->sort->value,
            $this->sort->value,
        );
        if ($this->limit > 0) {
            $sql .= sprintf(' LIMIT %d OFFSET %d', $this->limit, $this->offset);
        }
        return $sql;
    }

    #[Override]
    public function isFast(): bool
    {
        return !empty($this->fastq);
    }

    #[Override]
    public function getFastq(): string
    {
        return $this->fastq;
    }

    #[Override]
    public function getUserQuery(): string
    {
        return trim($this->queryString . ' ' . $this->extendedQuery);
    }

    #[Override]
    public function hasUserQuery(): bool
    {
        return !empty($this->queryString) || !empty($this->extendedQuery);
    }

    #[Override]
    public function getRelatedOrigin(): ?EntityType
    {
        return $this->relatedOrigin;
    }

    #[Override]
    public function getFilterSql(): string
    {
        return $this->filterSql . $this->getReactionSearch()->getFilterSql();
    }

    #[Override]
    public function bindFilterValues(PDOStatement $req): void
    {
        $this->getReactionSearch()->bindValues($req);
    }

    #[Override]
    public function setSkipOrderPinned(bool $value): void
    {
        $this->skipOrderPinned = $value;
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
        if (!empty($query->get('fastq'))) {
            $this->fastq = trim($query->getString('fastq'));
        }
        if (!empty($query->get('extended'))) {
            $this->extendedQuery = trim($query->getString('extended'));
        }
        if ($query->getInt('full') === 1) {
            $this->full = true;
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
        // filter by the current team only when the effective scope is Team
        if ($scope === Scope::Team) {
            $this->appendFilterSql(FilterableColumn::Team, $this->requester->team ?? 0);
        }
        // TAGS SEARCH
        if (!empty(($query->all('tags'))[0])) {
            // get all the ids with that tag
            $tags = $query->all('tags');
            $Tags2Entity = new Tags2Entity($this->requester, $this->entityType);
            $this->filterSql = Tools::getIdFilterSql($Tags2Entity->getEntitiesIdFromTags('tag', $tags, $scope));
            $this->entityFilterSql = $this->filterSql;
        }

        // RELATED FILTER
        if (Check::id($query->getInt('related')) !== false) {
            $this->appendFilterSql(FilterableColumn::Related, $query->getInt('related'));
            $this->relatedOrigin = EntityType::tryFrom($query->getAlpha('related_origin')) ?? $this->entityType;
        }

        // COMPOUND FILTER
        // use the compounds link table so only entries actually linked to the compound are returned,
        // and not entries where a compound property happens to match a text search
        // a compound the requester cannot read matches nothing: its links must not be revealed by enumerating ids
        if (Check::id($query->getInt('compound')) !== false) {
            $this->addEntityFilterSql(sprintf(
                ' AND entity.id IN (SELECT c2e_filter.entity_id FROM compounds2%s AS c2e_filter
                    INNER JOIN compounds AS compound_filter ON (compound_filter.id = c2e_filter.compound_id)
                    WHERE c2e_filter.compound_id = %d AND %s)',
                $this->entityType->value,
                $query->getInt('compound'),
                new CompoundPermissions($this->requester)->getCanCondition('compound_filter'),
            ));
        }

        // Note: we use getString() and not getInt() because values can be string separated (1,5)
        // CATEGORY FILTER
        // cat is for backward compatibility
        $this->addEntityFilterSql($this->getSqlIn('entity.category', $query->getString('cat')));
        $this->addEntityFilterSql($this->getSqlIn('entity.category', $query->getString('category')));
        // BOOKABLE
        $this->addEntityFilterSql($this->getSqlIn('entity.is_bookable', $query->getString('bookable')));
        // STATUS FILTER
        $this->addEntityFilterSql($this->getSqlIn('entity.status', $query->getString('status')));
        // OWNER (USERID) FILTER
        $this->addEntityFilterSql($this->getSqlIn('entity.userid', $query->getString('owner')));
        // LOCK FILTER: 0 or 1, use getInt()
        $this->addEntityFilterSql($this->getSqlIn('entity.locked', $query->getString('locked')));
        // TIMESTAMPED FILTER, same as lock
        $this->addEntityFilterSql($this->getSqlIn('entity.timestamped', $query->getString('timestamped')));
        // RATING FILTER
        $this->addEntityFilterSql($this->getSqlIn('entity.rating', $query->getString('rating')));
    }

    /**
     * Built on first use only: DisplayParams are also built from the request when reading a single entry,
     * and that must not validate the reaction search
     */
    private function getReactionSearch(): ReactionSearch
    {
        return $this->ReactionSearch ??= new ReactionSearch(
            $this->entityType,
            $this->getQuery(),
            requester: $this->requester,
            entityScopeSql: $this->entityFilterSql . $this->getStatesSql('entity'),
        );
    }

    private function addEntityFilterSql(string $sql): void
    {
        $this->filterSql .= $sql;
        $this->entityFilterSql .= $sql;
    }

    /**
     * Create an SQL string to add a filter from a comma separated list of int
     * possibly including null value. Ugly but works.
     */
    private function getSqlIn(string $column, string|int $input): string
    {
        $input = (string) $input;
        // Do not use empty() here as "0" is a valid filter value.
        if ($input === '') {
            return '';
        }
        $exploded = explode(',', $input);
        $conditions = array();
        $numbers = array();
        foreach ($exploded as $value) {
            $value = trim($value);

            if (strtolower($value) === 'null') {
                $conditions[] = sprintf('%s IS NULL', $column);
                continue;
            }
            $numbers[] = (int) $value;
        }
        if ($numbers) {
            $numbers = array_unique($numbers);
            // Add the IN condition to the list so it can be grouped with IS NULL below
            $conditions[] = sprintf('%s IN (%s)', $column, implode(', ', $numbers));
        }
        if (!$conditions) {
            return '';
        }
        // group all conditions to preserve SQL operator precedence when OR is present.
        return sprintf(' AND (%s)', implode(' OR ', $conditions));
    }
}
