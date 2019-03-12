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

use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Experiments;

/**
 * Make a CSV file from a list of id and a type
 */
class MakeCsv extends AbstractMake
{
    /** @var string $fileName a sha512 sum */
    public $fileName;

    /** @var array $list the lines in the csv file */
    private $list = array();

    /** @var string $idList the input ids */
    private $idList;

    /**
     * Give me a list of id+id+id and a type, I make good csv for you
     *
     * @param AbstractEntity $entity
     * @param string $idList 1+4+5+2
     */
    public function __construct(AbstractEntity $entity, $idList)
    {
        parent::__construct($entity);

        $this->fileName = $this->getUniqueString();
        $this->filePath = $this->getTmpPath() . $this->fileName;

        $this->idList = $idList;

        // set the column names
        $this->list[] = $this->getColumns();

        // main loop
        $this->loopIdArr();
    }

    /**
     * Here we populate the first row: it will be the column names
     *
     * @return array
     */
    private function getColumns(): array
    {
        if ($this->Entity instanceof Experiments) {
            return array('id', 'date', 'title', 'content', 'status', 'elabid', 'url');
        }
        return  array('id', 'date', 'title', 'description', 'category', 'rating', 'url');
    }

    /**
     * Main loop
     *
     * @return void
     */
    private function loopIdArr(): void
    {
        $idArr = explode(" ", $this->idList);
        foreach ($idArr as $id) {
            $this->Entity->setId((int) $id);
            $permissions = $this->Entity->getPermissions();
            if ($permissions['read']) {
                $this->addLine();
            }
        }
        $this->writeCsv($this->list);
    }

    /**
     * The column names will be different depending on type
     *
     * @return void
     */
    private function addLine(): void
    {
        if ($this->Entity instanceof Experiments) {
            $elabidOrRating = $this->Entity->entityData['elabid'];
        } else {
            $elabidOrRating = $this->Entity->entityData['rating'];
        }

        $this->list[] = array(
            $this->Entity->entityData['id'],
            $this->Entity->entityData['date'],
            htmlspecialchars_decode((string) $this->Entity->entityData['title'], ENT_QUOTES | ENT_COMPAT),
            html_entity_decode(strip_tags(htmlspecialchars_decode((string) $this->Entity->entityData['body'], ENT_QUOTES | ENT_COMPAT))),
            htmlspecialchars_decode((string) $this->Entity->entityData['category'], ENT_QUOTES | ENT_COMPAT),
            $elabidOrRating,
            $this->getUrl()
        );
    }

    /**
     * Return a nice name for the file
     *
     * @return string
     */
    public function getFileName(): string
    {
        return 'export.elabftw.csv';
    }
}
