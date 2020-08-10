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
use function in_array;

/**
 * This class holds the values for limit, offset, order and sort
 * A new instance will contain the default values
 *
 */
class DisplayParams
{
    /** @var int $limit */
    public $limit = 15;

    /** @var int $offset */
    public $offset = 0;

    /** @var string $order */
    public $order = 'date';

    /** @var string $sort */
    public $sort = 'desc';

    /** @var string $query the search from the top right search bar on experiments/database */
    public $query = '';

    /** @var string $searchType if this variable is not empty the error message shown will be different if there are no results */
    public $searchType = '';

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
        }
        if ((Check::id((int) $app->Request->query->get('cat')) !== false) || !empty($app->Request->query->get('tags')[0])) {
            $this->searchType = 'something';
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
        // NOTE: in 7.4 we will be able to use ??= here
        // load the pref from the user
        $this->order = $app->Users->userData['orderby'] ?? $this->order;

        // now get pref from the filter-order-sort menu
        $this->order = $app->Request->query->get('order') ?? $this->order;
    }

    private function setSort(App $app): void
    {
        // load the pref from the user
        $this->sort = $app->Users->userData['sort'] ?? $this->sort;

        // now get pref from the filter-order-sort menu
        $this->sort = $app->Request->query->get('sort') ?? $this->sort;

        if (!in_array($this->sort, array('asc', 'desc'), true)) {
            $this->sort = 'desc';
        }
    }
}
