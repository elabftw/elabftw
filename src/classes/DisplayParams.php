<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Enums\Orderby;
use Elabftw\Enums\Sort;
use Elabftw\Models\Users;
use Elabftw\Services\Check;
use Symfony\Component\HttpFoundation\Request;

/**
 * This class holds the values for limit, offset, order and sort
 * A new instance will contain the default values
 */
class DisplayParams
{
    public int $limit = 15;

    public int $offset = 0;

    public ?int $related;

    public Orderby $orderby = Orderby::Date;

    public Sort $sort = Sort::Desc;

    // the search from the top right search bar on experiments/database
    public string $query = '';

    // if this variable is not empty the error message shown will be different if there are no results
    public string $searchType = '';

    public function __construct(Users $Users, private Request $Request)
    {
        // load user's preferences first
        $this->limit = $Users->userData['limit_nb'];
        $this->orderby = Orderby::tryFrom($Users->userData['orderby']) ?? $this->orderby;
        $this->sort = Sort::tryFrom($Users->userData['sort']) ?? $this->sort;
    }

    /**
     * Adjust the settings based on the Request
     */
    public function adjust(): void
    {
        if ($this->Request->query->has('limit')) {
            $this->limit = Check::limit($this->Request->query->getInt('limit'));
        }
        if ($this->Request->query->has('offset') && Check::id($this->Request->query->getInt('offset')) !== false) {
            $this->offset = $this->Request->query->getInt('offset');
        }
        if (!empty($this->Request->query->get('q'))) {
            $this->query = $this->Request->query->filter('q', null, FILTER_SANITIZE_STRING);
            $this->searchType = 'query';
        }
        // now get pref from the filter-order-sort menu
        $this->sort = Sort::tryFrom($this->Request->query->getAlpha('sort')) ?? $this->sort;
        $this->orderby = Orderby::tryFrom($this->Request->query->getAlpha('order')) ?? $this->orderby;

        // RELATED FILTER
        if (Check::id((int) $this->Request->query->get('related')) !== false) {
            $this->searchType = 'related';
            $this->related = $this->Request->query->getInt('related');
        }
        // CATEGORY FILTER
        if ((Check::id((int) $this->Request->query->get('cat')) !== false) || !empty(((array) $this->Request->query->all('tags'))[0])) {
            $this->searchType = 'something';
        }
    }
}
