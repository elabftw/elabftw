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

use function sprintf;
use function trim;

/**
 * This class holds the values for limit, offset, order and sort
 * It is based on user preferences, overridden by request parameters
 */
class DisplayParams extends BaseQueryParams
{
    public string $filterSql = '';

    // the search from the top right search bar on experiments/database
    public string $queryString = '';

    // the extended search query
    public string $extendedQuery = '';

    public ?EntityType $relatedOrigin = null;

    public function __construct(
        private Users $requester,
        public EntityType $entityType,
        protected ?InputBag $query = null,
        public Orderby $orderby = Orderby::Lastchange,
        public Sort $sort = Sort::Desc,
        public int $limit = 15,
        public int $offset = 0,
        public bool $includeArchived = false,
        public array $states = array(State::Normal),
    ) {
        parent::__construct($query);
        // load user's preferences
        $this->limit = $requester->userData['limit_nb'] ?? $this->limit;
        $this->orderby = Orderby::tryFrom($requester->userData['orderby'] ?? $this->orderby->value) ?? $this->orderby;
        $this->sort = Sort::tryFrom($requester->userData['sort'] ?? $this->sort->value) ?? $this->sort;
        // then load from query
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
            'ORDER BY %s %s, entity.id %s LIMIT %d OFFSET %d',
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
        $scope = $this->requester->userData['scope_' . $this->entityType->value];
        if (Check::id($query->getInt('scope')) !== false) {
            $scope = $query->getInt('scope');
        }

        // filter by user if we don't want to show the rest of the team, only for experiments
        // looking for an owner will bypass the user preference
        // same with an extended search: we show all
        if ($scope === Scope::User->value && empty($query->get('owner')) && empty($query->get('extended'))) {
            // Note: the cast to int is necessary here (not sure why)
            $this->appendFilterSql(FilterableColumn::Owner, $this->requester->userData['userid']);
        }
        if ($this->requester->userData['scope_' . $this->entityType->value] === Scope::Team->value) {
            $this->appendFilterSql(FilterableColumn::Team, $this->requester->team ?? 0);
        }
        // TAGS SEARCH
        if (!empty(($query->all('tags'))[0])) {
            // get all the ids with that tag
            $tags = $query->all('tags');
            $Tags2Entity = new Tags2Entity($this->entityType);
            $this->filterSql = Tools::getIdFilterSql($Tags2Entity->getEntitiesIdFromTags('tag', $tags));
        }

        // RELATED FILTER
        if (Check::id($query->getInt('related')) !== false) {
            $this->appendFilterSql(FilterableColumn::Related, $query->getInt('related'));
            $this->relatedOrigin = EntityType::tryFrom($query->getAlpha('related_origin')) ?? $this->entityType;
        }
        // CATEGORY FILTER
        if (Check::id($query->getInt('cat')) !== false) {
            $this->appendFilterSql(FilterableColumn::Category, $query->getInt('cat'));
        }
        // STATUS FILTER
        if (Check::id($query->getInt('status')) !== false) {
            $this->appendFilterSql(FilterableColumn::Status, $query->getInt('status'));
        }

        // OWNER (USERID) FILTER
        if (Check::id($query->getInt('owner')) !== false) {
            $this->appendFilterSql(FilterableColumn::Owner, $query->getInt('owner'));
        }
    }
}
