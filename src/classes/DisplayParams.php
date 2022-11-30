<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Enums\FilterableColumn;
use Elabftw\Enums\Orderby;
use Elabftw\Enums\Sort;
use Elabftw\Models\Users;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;
use Symfony\Component\HttpFoundation\Request;
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

    // if this variable is not empty the error message shown will be different if there are no results
    public string $searchType = '';

    // start metadata stuff
    public bool $hasMetadataSearch = false;

    public array $metadataFilter = array();

    public array $metadataKey = array();

    public array $metadataValuePath = array();

    public array $metadataValue = array();

    private array $metadataHaving = array();
    // end metadata stuff

    public function __construct(Users $Users, private Request $Request)
    {
        // load user's preferences first
        $this->limit = $Users->userData['limit_nb'];
        $this->orderby = Orderby::tryFrom($Users->userData['orderby']) ?? $this->orderby;
        $this->sort = Sort::tryFrom($Users->userData['sort']) ?? $this->sort;
        $this->adjust();
    }

    public function appendFilterSql(FilterableColumn $column, int $value): void
    {
        $this->filterSql .= sprintf(' AND %s = %d', $column->value, $value);
    }

    public function getMetadataHavingSql(): string
    {
        if (!empty($this->metadataHaving)) {
            return 'HAVING ' . implode(' AND ', $this->metadataHaving);
        }
        return '';
    }

    private function addMetadataFilter(string $key, string $value): void
    {
        $this->hasMetadataSearch = true;
        $i = count($this->metadataKey);
        // Note: the key is double quoted so spaces are not an issue
        $key = '$.extra_fields."' . Filter::sanitize($key) . '"';
        $this->metadataKey[] = $key;
        $this->metadataValuePath[] = $key . '.value';
        $this->metadataValue[] = Filter::sanitize($value);
        $this->metadataFilter[] = sprintf(" AND JSON_CONTAINS_PATH(entity.metadata, 'one', :metadata_key_%d) ", $i);
        $this->metadataHaving[] = sprintf(' JSON_UNQUOTE(JSON_EXTRACT(entity.metadata, :metadata_value_path_%d)) LIKE :metadata_value_%d', $i, $i);
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
            $this->query = (string) $this->Request->query->get('q', '');
            $this->searchType = 'query';
        }
        if (!empty($this->Request->query->get('extended'))) {
            $this->query = trim((string) $this->Request->query->get('extended'));
            $this->searchType = 'query';
        }
        // now get pref from the filter-order-sort menu
        $this->sort = Sort::tryFrom($this->Request->query->getAlpha('sort')) ?? $this->sort;
        $this->orderby = Orderby::tryFrom($this->Request->query->getAlpha('order')) ?? $this->orderby;

        // RELATED FILTER
        if (Check::id($this->Request->query->getInt('related')) !== false) {
            $this->appendFilterSql(FilterableColumn::Related, $this->Request->query->getInt('related'));
            $this->searchType = 'related';
        }
        // CATEGORY FILTER
        if (Check::id($this->Request->query->getInt('cat')) !== false) {
            $this->appendFilterSql(FilterableColumn::Category, $this->Request->query->getInt('cat'));
            $this->searchType = 'category';
        }

        // OWNER (USERID) FILTER
        if (Check::id($this->Request->query->getInt('owner')) !== false) {
            $this->appendFilterSql(FilterableColumn::Owner, $this->Request->query->getInt('owner'));
            $this->searchType = 'owner';
        }
        // METADATA SEARCH
        foreach ($this->Request->query->all('metakey') as $i => $metakey) {
            if (!empty($metakey)) {
                $this->addMetadataFilter($metakey, $this->Request->query->all('metavalue')[$i]);
            }
        }
    }
}
