<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Enums\EntityType;
use Elabftw\Enums\FilterableColumn;
use Elabftw\Enums\Orderby;
use Elabftw\Enums\Scope;
use Elabftw\Enums\Sort;
use Elabftw\Models\Tags2Entity;
use Elabftw\Models\Users;
use Elabftw\Services\Check;
use Override;
use Symfony\Component\HttpFoundation\Request;

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
    public string $query = '';

    // the extended search query
    public string $extendedQuery = '';

    public ?EntityType $relatedOrigin = null;

    public function __construct(private Users $Users, Request $Request, public EntityType $entityType)
    {
        // load user's preferences first
        $this->limit = $Users->userData['limit_nb'] ?? $this->limit;
        $this->orderby = Orderby::tryFrom($Users->userData['orderby'] ?? $this->orderby->value) ?? $this->orderby;
        $this->sort = Sort::tryFrom($Users->userData['sort'] ?? $this->sort->value) ?? $this->sort;
        // then load from query
        parent::__construct($Request);
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
        if (!empty($this->Request->query->get('q'))) {
            $this->query = trim($this->Request->query->getString('q'));
        }
        if (!empty($this->Request->query->get('extended'))) {
            $this->extendedQuery = trim($this->Request->query->getString('extended'));
        }

        // SCOPE FILTER
        // default scope is the user setting, but can be overridden by query param
        $scope = $this->Users->userData['scope_' . $this->entityType->value];
        if (Check::id($this->Request->query->getInt('scope')) !== false) {
            $scope = $this->Request->query->getInt('scope');
        }

        // filter by user if we don't want to show the rest of the team, only for experiments
        // looking for an owner will bypass the user preference
        // same with an extended search: we show all
        if ($scope === Scope::User->value && empty($this->Request->query->get('owner')) && empty($this->Request->query->get('extended'))) {
            $this->appendFilterSql(FilterableColumn::Owner, $this->Users->userData['userid']);
        }
        if ($this->Users->userData['scope_' . $this->entityType->value] === Scope::Team->value) {
            $this->appendFilterSql(FilterableColumn::Team, $this->Users->team ?? 0);
        }
        // TAGS SEARCH
        if (!empty(($this->Request->query->all('tags'))[0])) {
            // get all the ids with that tag
            $tags = $this->Request->query->all('tags');
            $Tags2Entity = new Tags2Entity($this->entityType);
            $this->filterSql = Tools::getIdFilterSql($Tags2Entity->getEntitiesIdFromTags('tag', $tags));
        }

        // RELATED FILTER
        if (Check::id($this->Request->query->getInt('related')) !== false) {
            $this->appendFilterSql(FilterableColumn::Related, $this->Request->query->getInt('related'));
            $this->relatedOrigin = EntityType::tryFrom($this->Request->query->getAlpha('related_origin')) ?? $this->entityType;
        }
        // CATEGORY FILTER
        if (Check::id($this->Request->query->getInt('cat')) !== false) {
            $this->appendFilterSql(FilterableColumn::Category, $this->Request->query->getInt('cat'));
        }
        // STATUS FILTER
        if (Check::id($this->Request->query->getInt('status')) !== false) {
            $this->appendFilterSql(FilterableColumn::Status, $this->Request->query->getInt('status'));
        }

        // OWNER (USERID) FILTER
        if (Check::id($this->Request->query->getInt('owner')) !== false) {
            $this->appendFilterSql(FilterableColumn::Owner, $this->Request->query->getInt('owner'));
        }

    }
}
