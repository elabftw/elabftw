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

    /** @var App $App */
    private $App;

    public function __construct(App $app)
    {
        $this->App = $app;

        $this->setLimit();
        $this->setOffset();
        $this->setQuery();
        $this->setSort();
        $this->setOrder();
        // RELATED FILTER
        if (Check::id((int) $this->App->Request->query->get('related')) !== false) {
            $this->searchType = 'related';
        }
        if ((Check::id((int) $this->App->Request->query->get('cat')) !== false) || !empty($this->App->Request->query->get('tags')[0])) {
            $this->searchType = 'something';
        }
    }

    private function setLimit(): void
    {
        $limit = (int) ($this->App->Users->userData['limit_nb'] ?? 15);
        if ($this->App->Request->query->has('limit')) {
            $limit = Check::limit((int) $this->App->Request->query->get('limit'));
        }
        $this->limit = $limit;
    }

    private function setOffset(): void
    {
        if ($this->App->Request->query->has('offset') && Check::id((int) $this->App->Request->query->get('offset')) !== false) {
            $this->offset = (int) $this->App->Request->query->get('offset');
        }
    }

    private function setQuery(): void
    {
        if (!empty($this->App->Request->query->get('q'))) {
            $this->query = $this->App->Request->query->filter('q', null, FILTER_SANITIZE_STRING);
            $this->searchType = 'query';
        }
    }

    private function setOrder(): void
    {
        // NOTE: in 7.4 we will be able to use ??= here
        // load the pref from the user
        $this->order = $this->App->Users->userData['orderby'] ?? $this->order;

        // now get pref from the filter-order-sort menu
        $this->order = $this->App->Request->query->get('order') ?? $this->order;
    }

    private function setSort(): void
    {
        // load the pref from the user
        $this->sort = $this->App->Users->userData['sort'] ?? $this->sort;

        // now get pref from the filter-order-sort menu
        $this->sort = $this->App->Request->query->get('sort') ?? $this->sort;

        if (!in_array($this->sort, array('asc', 'desc'), true)) {
            $this->sort = 'desc';
        }
    }
}
