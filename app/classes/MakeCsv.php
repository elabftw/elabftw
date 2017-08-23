<?php
/**
 * \Elabftw\Elabftw\MakeCsv
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

/**
 * Make a CSV file from a list of id and a type
 */
class MakeCsv extends AbstractMake
{
    /** @var string $fileName a sha512 sum */
    public $fileName;

    /** @var string $filePath the full path of the file */
    public $filePath;

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
        $this->filePath = $this->getFilePath($this->fileName, true);

        $this->idList = $idList;

        // set the column names
        $this->list[] = $this->populateFirstLine();

        // main loop
        $this->loopIdArr();
    }

    /**
     * Return a nice name for the file
     *
     * @return string
     */
    public function getCleanName()
    {
        return 'export.elabftw.csv';
    }
    /**
     * Here we populate the first row: it will be the column names
     *
     */
    private function populateFirstLine()
    {
        if ($this->Entity->type === 'experiments') {
            return array('id', 'date', 'title', 'content', 'status', 'elabid', 'url');
        }
        return  array('id', 'date', 'title', 'description', 'category', 'rating', 'url');
    }

    /**
     * Main loop
     *
     */
    private function loopIdArr()
    {
        $idArr = explode(" ", $this->idList);
        foreach ($idArr as $id) {
            $this->Entity->setId($id);
            $permissions = $this->Entity->getPermissions();
            if ($permissions['read']) {
                $this->addLine();
            }
        }
        $this->writeCsv();
    }

    /**
     * The column names will be different depending on type
     */
    private function addLine()
    {
        if ($this->Entity->type === 'experiments') {
            $elabidOrRating = $this->Entity->entityData['elabid'];
        } else {
            $elabidOrRating = $this->Entity->entityData['rating'];
        }

        $this->list[] = array(
            $this->Entity->entityData['id'],
            $this->Entity->entityData['date'],
            htmlspecialchars_decode($this->Entity->entityData['title'], ENT_QUOTES | ENT_COMPAT),
            html_entity_decode(strip_tags(htmlspecialchars_decode($this->Entity->entityData['body'], ENT_QUOTES | ENT_COMPAT))),
            htmlspecialchars_decode($this->Entity->entityData['category'], ENT_QUOTES | ENT_COMPAT),
            $elabidOrRating,
            $this->getUrl()
        );
    }

    /**
     * Write our file
     */
    private function writeCsv()
    {
        $fp = fopen($this->filePath, 'w+');
        // utf8 headers
        fwrite($fp, "\xEF\xBB\xBF");
        foreach ($this->list as $fields) {
                fputcsv($fp, $fields);
        }
        fclose($fp);
    }
}
