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
use Elabftw\Models\Users;
use Elabftw\Services\Check;
use PDO;
use Symfony\Component\HttpFoundation\Request;

use function implode;
use function sprintf;
use function trim;

/**
 * This class holds the values for limit, offset, order and sort
 * It is based on user preferences, overridden by request parameters
 */
class DisplayParams
{
    public int $limit = 15;

    public int $offset = 0;

    public string $filterSql = '';

    public Orderby $orderby = Orderby::Date;

    public Sort $sort = Sort::Desc;

    // the search from the top right search bar on experiments/database
    public string $query = '';

    // the extended search query
    public string $extendedQuery = '';

    public ?EntityType $relatedOrigin = null;

    public bool $includeArchived = false;

    public function __construct(private Users $Users, private Request $Request, public EntityType $entityType)
    {
        // load user's preferences first
        $this->limit = $Users->userData['limit_nb'] ?? $this->limit;
        $this->orderby = Orderby::tryFrom($Users->userData['orderby'] ?? $this->orderby->value) ?? $this->orderby;
        $this->sort = Sort::tryFrom($Users->userData['sort'] ?? $this->sort->value) ?? $this->sort;
        $this->adjust();
        // we don't care about the value, so it can be 'on' from a checkbox or 1 or anything really
        $this->includeArchived = $this->Request->query->has('archived');
    }

    public function appendFilterSql(FilterableColumn $column, int $value): void
    {
        $this->filterSql .= sprintf(' AND %s = %d', $column->value, $value);
    }

    /**
     * Adjust the settings based on the Request
     */
    private function adjust(): void
    {
        if ($this->Request->query->has('limit')) {
            $this->limit = Check::limit($this->Request->query->getInt('limit'));
        }
        if ($this->Request->query->has('offset') && Check::id($this->Request->query->getInt('offset')) !== false) {
            $this->offset = $this->Request->query->getInt('offset');
        }
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
            // Note: the cast to int is necessary here (not sure why)
            $this->appendFilterSql(FilterableColumn::Owner, (int) $this->Users->userData['userid']);
        }
        if ($this->Users->userData['scope_' . $this->entityType->value] === Scope::Team->value) {
            $this->appendFilterSql(FilterableColumn::Team, $this->Users->team);
        }
        // TAGS SEARCH
        if (!empty(($this->Request->query->all('tags'))[0])) {
            // get all the ids with that tag
            $tags = $this->Request->query->all('tags');
            // look for item ids that have all the tags not only one of them
            // the HAVING COUNT is necessary to make an AND search between tags
            // Note: we cannot use a placeholder for the IN of the tags because we need the quotes
            $Db = Db::getConnection();
            $inPlaceholders = implode(' , ', array_map(function ($key) {
                return ":tag$key";
            }, array_keys($tags)));
            $sql = 'SELECT tags2entity.item_id FROM `tags2entity`
                INNER JOIN (SELECT id FROM tags WHERE tags.tag IN ( ' . $inPlaceholders . ' )) tg ON tags2entity.tag_id = tg.id
                WHERE tags2entity.item_type = :type GROUP BY item_id HAVING COUNT(DISTINCT tags2entity.tag_id) = :count';
            $req = $Db->prepare($sql);
            // bind the tags in IN clause
            foreach ($tags as $key => $tag) {
                $req->bindValue(":tag$key", $tag, PDO::PARAM_STR);
            }
            $req->bindValue(':type', $this->entityType->value, PDO::PARAM_STR);
            $req->bindValue(':count', count($tags), PDO::PARAM_INT);
            $req->execute();
            $this->filterSql = Tools::getIdFilterSql($req->fetchAll(PDO::FETCH_COLUMN));
        }
        // now get ordering/sorting parameters from the query string
        $this->sort = Sort::tryFrom($this->Request->query->getAlpha('sort')) ?? $this->sort;
        $this->orderby = Orderby::tryFrom($this->Request->query->getAlpha('order')) ?? $this->orderby;

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
