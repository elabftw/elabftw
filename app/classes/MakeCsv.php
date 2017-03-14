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
class MakeCsv extends Make
{
    /** our pdo object */
    protected $pdo;

    /** a sha512 sum */
    public $fileName;
    /** the full path of the file */
    public $filePath;

    /** the lines in the csv file */
    private $list = array();
    /** the input ids */
    private $idList;
    /** the input ids but in an array */
    private $idArr = array();
    /** Entity instance */
    private $Entity;

    /**
     * Give me a list of id+id+id and a type, I make good csv for you
     *
     * @param Entity $entity
     * @param string $idList 1+4+5+2
     */
    public function __construct(Entity $entity, $idList)
    {
        $this->pdo = Db::getConnection();
        $this->Entity = $entity;

        $this->fileName = $this->getFileName();
        $this->filePath = $this->getTempFilePath($this->fileName);

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
        return  array('title', 'description', 'id', 'date', 'type', 'rating', 'url');
    }

    /**
     * Main loop
     *
     */
    private function loopIdArr()
    {
        $this->idArr = explode(" ", $this->idList);
        foreach ($this->idArr as $id) {
            $this->Entity->setId($id);
            $this->Entity->populate();
            $permissions = $this->Entity->getPermissions();
            if ($permissions['read']) {
                $this->addLine();
            }
        }
        $this->writeCsv();
    }

    /**
     * Construct URL
     *
     * @param int $id The id of the current item
     * @return string URL
     */
    private function getUrl($id)
    {
        $url = 'https://' . $_SERVER['SERVER_NAME'] . Tools::getServerPort() . $_SERVER['PHP_SELF'];
        $needle = array('make.php', 'app/controllers/ExperimentsController.php');

        if ($this->Entity->type === 'experiments') {
            $url = str_replace($needle, 'experiments.php', $url);
        } else { //item
            $url = str_replace($needle, 'database.php', $url);
        }
        return $url . "?mode=view&id=" . $id;
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
            $this->getUrl($this->Entity->entityData['id'])
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
