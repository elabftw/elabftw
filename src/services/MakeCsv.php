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

use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Experiments;
use Elabftw\Traits\CsvTrait;

/**
 * Make a CSV file from a list of id and a type
 */
class MakeCsv extends AbstractMake
{
    use CsvTrait;

    // list of id to make csv from, separated by spaces: 4 8 15 16 23 42
    private string $idList;

    /**
     * Give me a list of "id id id" and a type, I make good csv for you
     */
    public function __construct(AbstractEntity $entity, string $idList)
    {
        parent::__construct($entity);
        $this->idList = $idList;
    }

    /**
     * Return a nice name for the file
     */
    public function getFileName(): string
    {
        return Filter::kdate() . '-export.elabftw.csv';
    }

    /**
     * Here we populate the first row: it will be the column names
     */
    protected function getHeader(): array
    {
        if ($this->Entity instanceof Experiments) {
            return array('id', 'date', 'title', 'content', 'status', 'elabid', 'url');
        }
        return  array('id', 'date', 'title', 'description', 'category', 'rating', 'url');
    }

    /**
     * Generate an array for the requested data
     */
    protected function getRows(): array
    {
        $rows = array();
        $idArr = explode(' ', $this->idList);
        foreach ($idArr as $id) {
            $this->Entity->setId((int) $id);
            $permissions = $this->Entity->getPermissions();
            if ($permissions['read']) {
                if ($this->Entity instanceof Experiments) {
                    $elabidOrRating = $this->Entity->entityData['elabid'];
                } else {
                    $elabidOrRating = $this->Entity->entityData['rating'];
                }
                $rows[] = array(
                    $this->Entity->entityData['id'],
                    $this->Entity->entityData['date'],
                    htmlspecialchars_decode((string) $this->Entity->entityData['title'], ENT_QUOTES | ENT_COMPAT),
                    html_entity_decode(strip_tags(htmlspecialchars_decode((string) $this->Entity->entityData['body'], ENT_QUOTES | ENT_COMPAT))),
                    htmlspecialchars_decode((string) $this->Entity->entityData['category'], ENT_QUOTES | ENT_COMPAT),
                    $elabidOrRating,
                    $this->getUrl(),
                );
            }
        }

        return $rows;
    }
}
