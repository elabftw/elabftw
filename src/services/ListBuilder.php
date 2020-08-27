<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\DisplayParams;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Database;
use Elabftw\Models\Experiments;

/**
 * Create lists of experiments/items for consumption by js code
 */
class ListBuilder
{
    /** @var AbstractEntity $Entity */
    private $Entity;

    public function __construct(AbstractEntity $entity)
    {
        $this->Entity = $entity;
    }

    /**
     * Get an array formatted for the autocomplete input (link and bind)
     *
     * @param string $term the query
     * @return array
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
     * @return array
     */
    public function getMentionList(string $term): array
    {
        $mentionArr = array();

        // add items from database
        $itemsArr = $this->getList($term);

        if ($this->Entity instanceof Database) {
            foreach ($itemsArr as $item) {
                $mentionArr[] = array('name' => "<a href='database.php?mode=view&id=" .
                    $item['id'] . "'>[" . $item['category'] . '] ' . $item['title'] . '</a>',
                );
            }
        }

        // experiments have a different category name (Experiment)
        if ($this->Entity instanceof Experiments) {
            foreach ($itemsArr as $item) {
                $mentionArr[] = array('name' => "<a href='experiments.php?mode=view&id=" .
                    $item['id'] . "'>[" . ngettext('Experiment', 'Experiments', 1) . '] ' . $item['title'] . '</a>',
                );
            }
        }

        return $mentionArr;
    }

    /**
     * Get a list of experiments/items with title containing $term
     *
     * @param string $term the query
     * @return array
     */
    private function getList(string $term): array
    {
        $term = filter_var($term, FILTER_SANITIZE_STRING);
        $this->Entity->titleFilter = " AND entity.title LIKE '%$term%'";

        return $this->Entity->readShow(new DisplayParams());
    }
}
