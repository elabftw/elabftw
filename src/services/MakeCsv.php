<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\Tools;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Experiments;

/**
 * Make a CSV file from a list of id and a type
 */
class MakeCsv extends AbstractMake
{
    /**
     * Give me a list of id+id+id and a type, I make good csv for you
     *
     * @param AbstractEntity $entity
     * @param string $idList 1+4+5+2
     */
    public function __construct(AbstractEntity $entity, $idList)
    {
        parent::__construct($entity);

        $this->outputContent = $this->makeCsv($this->getHeader(), $this->getRows($idList));
    }

    /**
     * Return a nice name for the file
     *
     * @return string
     */
    public function getFileName(): string
    {
        return Tools::kdate() . '-export.elabftw.csv';
    }

    /**
     * Here we populate the first row: it will be the column names
     *
     * @return array
     */
    private function getHeader(): array
    {
        if ($this->Entity instanceof Experiments) {
            return array('id', 'date', 'title', 'content', 'status', 'elabid', 'url');
        }
        return  array('id', 'date', 'title', 'description', 'category', 'rating', 'url');
    }

    /**
     * Generate an array for the requested data
     *
     * @return array
     */
    private function getRows($idList): array
    {
        $rows = array();
        $idArr = explode(" ", $idList);
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
                    $this->getUrl()
                );
            }
        }

        return $rows;
    }
}
