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

/**
 * Create lists of experiments/items for consumption by js code
 */
class ListBuilder
{
    /** @var AbstractEntity $Entity */
    private $Entity;

    public function __construct(AbstractEntity $entity, private int $catFilter = 0)
    {
        $this->Entity = $entity;
    }

    /**
     * Get an array formatted for the autocomplete input (link and bind)
     *
     * @param string $term the query
     */
    public function getAutocomplete(string $term): array
    {
        $items = $this->getList($term);

        $linksArr = array();
        foreach ($items as $item) {
            $linksArr[] = $item['id'] . ' - ' . $item['category'] . ' - ' . substr($item['title'], 0, 60);
        }
        return $linksArr;
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

        return $mentionArr;
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
        if ($this->catFilter !== 0) {
            $this->Entity->addFilter('categoryt.id', (string) $this->catFilter);
        }

        return $this->Entity->readShow(new DisplayParams());
    }
}
