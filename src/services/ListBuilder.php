<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\DisplayParams;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use function mb_convert_encoding;

/**
 * Create lists of experiments/items for consumption by js code
 */
class ListBuilder
{
    private string $filterValue = '';

    public function __construct(private AbstractEntity $Entity)
    {
    }

    /**
     * Get an array formatted for the autocomplete input (link and bind)
     *
     * @param string $term the query
     * @param string $filterValue Narrow down search space: Either a category id (items) or an author full name (experiments)
     */
    public function getAutocomplete(string $term, string $filterValue = ''): array
    {
        $this->filterValue = filter_var($filterValue, FILTER_SANITIZE_STRING) ?: '';
        $items = $this->getList($term);

        $linksArr = array();
        foreach ($items as $item) {
            $linksArr[] = array(
                'label' => (
                    $this->filterValue === ''
                    ? ($this->Entity instanceof Experiments ? $item['fullname'] : '') . $item['category'] . ' - '
                    : ''
                ) . $item['date'] . ' - ' . substr($item['title'], 0, 60),
                'value' => $item['id'],
            );
        }

        return $this->fixMalformedUTF8($linksArr);
    }

    /**
     * Get an array of a mix of experiments and database items
     * for use with the mention plugin of tinymce (# autocomplete)
     *
     * @param string $term the query
     */
    public function getMentionList(string $term): array
    {
        $mentionArr = array();

        // add items from database
        $itemsArr = $this->getList($term);

        if ($this->Entity instanceof Items) {
            foreach ($itemsArr as $item) {
                $mentionArr[] = array(
                    'name' => sprintf('[%s] %s', $item['category'], $item['title']),
                    'id' => $item['id'],
                    'type' => $this->Entity->type,
                    'page' => $this->Entity->page,
                );
            }
        }

        // experiments have a different category name (Experiment)
        if ($this->Entity instanceof Experiments) {
            foreach ($itemsArr as $item) {
                $mentionArr[] = array(
                    'name' => sprintf('[%s] %s', ngettext('Experiment', 'Experiments', 1), $item['title']),
                    'id' => $item['id'],
                    'type' => $this->Entity->type,
                    'page' => $this->Entity->page,
                );
            }
        }

        return $this->fixMalformedUTF8($mentionArr);
    }

    /**
     * Get a list of experiments/items with title containing $term
     *
     * @param string $term the query
     */
    private function getList(string $term): array
    {
        $term = filter_var($term, FILTER_SANITIZE_STRING);
        $this->Entity->addToExtendedFilter(" AND entity.title LIKE '%$term%'");
        if ($this->filterValue !== '') {
            if ($this->Entity instanceof Items) {
                $this->Entity->addFilter('categoryt.id', $this->filterValue);
            }
            if ($this->Entity instanceof Experiments) {
                $this->Entity->addToExtendedFilter(" AND CONCAT(users.firstname, ' ', users.lastname) LIKE '" . $this->filterValue . "'");
            }
        }

        return $this->Entity->readShow(new DisplayParams());
    }

    /**
     * fix issue with Malformed UTF-8 characters, possibly incorrectly encoded
     * see #2404
     *
     * @param array $arr
     */
    private function fixMalformedUTF8(array $arr): array
    {
        return mb_convert_encoding($arr, 'UTF-8', 'UTF-8');
    }
}
