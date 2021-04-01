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

use Elabftw\Services\Check;

/**
 * This class holds the values for limit, offset, order and sort
 * A new instance will contain the default values
 *
 */
class DisplayParams
{
    public int $limit = 15;

    public int $offset = 0;

    public ?int $related;

    public string $order = 'date';

    public string $sort = 'desc';

    // the search from the top right search bar on experiments/database
    public string $query = '';

    // if this variable is not empty the error message shown will be different if there are no results
    public string $searchType = '';

    /**
     * Use the user preferences and request to adjust parameters
     */
    public function adjust(App $app): void
    {
        $this->setLimit($app);
        $this->setOffset($app);
        $this->setQuery($app);
        $this->setSort($app);
        $this->setOrder($app);
        // RELATED FILTER
        if (Check::id((int) $app->Request->query->get('related')) !== false) {
            $this->searchType = 'related';
            $this->setRelated($app);
        }
        if ((Check::id((int) $app->Request->query->get('cat')) !== false) || !empty($app->Request->query->get('tags')[0])) {
            $this->searchType = 'something';
        }
    }

    /**
     * Order by in sql
     */
    public function getOrderSql(): string
    {
        switch ($this->order) {
            case 'cat':
                return 'categoryt.id';
            case 'rating':
                return 'entity.rating';
            case 'title':
                return 'entity.title';
            case 'id':
                return 'entity.id';
            case 'lastchange':
                return 'entity.lastchange';
            case 'comment':
                return 'commentst.recent_comment';
            case 'user':
                return 'entity.userid';
            default:
                return 'date';
        }
    }

    private function setLimit(App $app): void
    {
        $limit = (int) ($app->Users->userData['limit_nb'] ?? 15);
        if ($app->Request->query->has('limit')) {
            $limit = Check::limit((int) $app->Request->query->get('limit'));
        }
        $this->limit = $limit;
    }

    private function setOffset(App $app): void
    {
        if ($app->Request->query->has('offset') && Check::id((int) $app->Request->query->get('offset')) !== false) {
            $this->offset = (int) $app->Request->query->get('offset');
        }
    }

    private function setQuery(App $app): void
    {
        if (!empty($app->Request->query->get('q'))) {
            $this->query = $app->Request->query->filter('q', null, FILTER_SANITIZE_STRING);
            $this->searchType = 'query';
        }
    }

    private function setOrder(App $app): void
    {
        // load the pref from the user
        $this->order = $app->Users->userData['orderby'] ?? $this->order;

        // now get pref from the filter-order-sort menu
        $this->order = $app->Request->query->get('order') ?? $this->order;
    }

    private function setRelated(App $app): void
    {
        $this->related = (int) $app->Request->query->get('related') ?? $this->related;
    }

    private function setSort(App $app): void
    {
        // load the pref from the user
        $this->sort = $app->Users->userData['sort'] ?? $this->sort;

        // now get pref from the filter-order-sort menu
        if (!empty($app->Request->query->get('sort'))) {
            $this->sort = Check::sort($app->Request->query->get('sort'));
        }
    }
}
