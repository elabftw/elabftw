<?php
/**
 * \Elabftw\Elabftw\MakeZip
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use ZipArchive;
use Exception;

/**
 * Make a zip archive from experiment or db item
 */
class MakeZip extends Make
{
    /** our pdo object */
    protected $pdo;
    /** the zip object */
    private $zip;
    /** Entity instance */
    private $Entity;

    /** the input ids */
    private $idList;
    /** the input ids but in an array */
    private $idArr = array();
    /** files to be deleted by destructor */
    private $filesToDelete = array();
    /** a formatted title */
    private $cleanTitle;
    /** a sha512 sum */
    public $fileName;
    /** full path of file */
    public $filePath;
    /** name of folder */
    private $folder;
    /** the path to attached files in the zip */
    private $fileArr = array();
    /** array that will be converted to json */
    private $jsonArr = array();


    /**
     * Give me an id list and a type, I make good zip for you
     *
     * @param Entity $entity
     * @param string $idList 1+3+5+8
     * @throws Exception if we don't have ZipArchive extension
     */
    public function __construct(Entity $entity, $idList)
    {
        $this->pdo = Db::getConnection();
        $this->Entity = $entity;

        // we check first if the zip extension is here
        if (!class_exists('ZipArchive')) {
            throw new Exception(_("You are missing the ZipArchive class in php. Uncomment the line extension=zip.so in php.ini file."));
        }

        $this->idList = $idList;

        $this->fileName = $this->getFileName();
        $this->filePath = $this->getTempFilePath($this->fileName);

        $this->createZipArchive();
        $this->loopIdArr();

    }

    /**
     * This is the name of the file that will get downloaded
     *
     * @return string
     */
    public function getCleanName()
    {
        $ext = '.elabftw.zip';

        if (count($this->idArr) === 1) {
            return $this->Entity->entityData['date'] . "-" . $this->cleanTitle . $ext;
        }
        return Tools::kdate() . $ext;
    }

    /**
     * Initiate the zip object and the archive
     *
     */
    private function createZipArchive()
    {
        $this->zip = new \ZipArchive;

        if (!$this->zip->open($this->filePath, ZipArchive::CREATE)) {
            throw new Exception('Could not open zip file!');
        }
    }

    /**
     * Make a title without special char for folder inside .zip
     *
     * @return null
     */
    private function setCleanTitle()
    {
        $this->cleanTitle = preg_replace('/[^A-Za-z0-9]/', '_', stripslashes($this->Entity->entityData['title']));
    }

    /**
     * Add the .asn1 token to the zip archive if the experiment is timestamped
     *
     * @param int $id The id of current item we are zipping
     */
    private function addAsn1Token($id)
    {
        if ($this->Entity->type === 'experiments' && $this->Entity->entityData['timestamped'] === '1') {
            // SQL to get the path of the token
            $sql = "SELECT real_name, long_name FROM uploads WHERE item_id = :id AND type = 'timestamp-token' LIMIT 1";
            $req = $this->pdo->prepare($sql);
            $req->bindParam(':id', $id);
            $req->execute();
            $token = $req->fetch();
            // add it to the .zip
            $this->zip->addFile(ELAB_ROOT . 'uploads/' . $token['long_name'], $this->folder . "/" . $token['real_name']);
        }
    }

    /**
     * Folder begins with date for experiments
     *
     */
    private function nameFolder()
    {
        if ($this->Entity->type === 'experiments') {
            $this->folder = $this->Entity->entityData['date'] . "-" . $this->cleanTitle;
        } else { // items
            $this->folder = $this->Entity->entityData['category'] . " - " . $this->cleanTitle;
        }
    }

    /**
     * Add attached files (if any)
     *
     * @param int $id The id of the item we are zipping
     */
    private function addAttachedFiles($id)
    {
        $real_name = array();
        $long_name = array();

        // SQL to get filesattached (of the right type)
        if ($this->Entity->type === 'experiments') {
            $sql = "SELECT * FROM uploads WHERE item_id = :id AND (type = :type OR type = 'exp-pdf-timestamp')";
        } else {
            $sql = "SELECT * FROM uploads WHERE item_id = :id AND type = :type";
        }
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $id);
        $req->bindParam(':type', $this->Entity->type);
        $req->execute();
        while ($uploads = $req->fetch()) {
            $real_name[] = $uploads['real_name'];
            $long_name[] = $uploads['long_name'];
        }

        // files attached ?
        $fileNb = count($real_name);
        $real_names_so_far = array();
        if ($fileNb > 0) {
            for ($i = 0; $i < $fileNb; $i++) {
                $realName = $real_name[$i];

                // if we have a file with the same name, it shouldn't overwrite the previous one
                if (in_array($realName, $real_names_so_far)) {
                    $realName = $i . '_' . $real_name[$i];
                }
                $real_names_so_far[] = $realName;
                // add files to archive
                $this->zip->addFile(ELAB_ROOT . 'uploads/' . $long_name[$i], $this->folder . "/" . $realName);
                // reference them in the json file
                $this->fileArr[] = $this->folder . "/" . $real_name[$i];
            }
        }
    }

    /**
     * Add PDF to archive
     *
     */
    private function addPdf()
    {
        $pdf = new MakePdf($this->Entity, true);
        $this->zip->addFile($pdf->filePath, $this->folder . '/' . $pdf->getCleanName());
        $this->filesToDelete[] = $pdf->filePath;
    }

    /**
     * Add a CSV file
     *
     * @param int $id The id of the item we are zipping
     */
    private function addCsv($id)
    {
        // add CSV file to archive
        $csv = new MakeCsv($this->Entity, $id);
        $this->zip->addFile($csv->filePath, $this->folder . "/" . $this->cleanTitle . ".csv");
        $this->filesToDelete[] = $csv->filePath;
    }

    /**
     * Add the (hidden) .elabftw.json file useful for reimport
     *
     */
    private function addJson()
    {
        $json = json_encode($this->jsonArr);
        $jsonPath = ELAB_ROOT . 'uploads/tmp/' . hash("sha512", uniqid(rand(), true)) . '.json';
        $tf = fopen($jsonPath, 'w+');
        fwrite($tf, $json);
        fclose($tf);
        $this->zip->addFile($jsonPath, ".elabftw.json");
        $this->filesToDelete[] = $jsonPath;
    }

    /**
     * This is where the magic happens
     *
     * @param int $id The id of the item we are zipping
     */
    private function addToZip($id)
    {
        $this->Entity->setId($id);
        $this->Entity->populate();
        $this->setCleanTitle();
        $permissions = $this->Entity->getPermissions();
        if ($permissions['read']) {
            $this->nameFolder();
            $this->addAsn1Token($id);
            $this->addAttachedFiles($id);
            $this->addCsv($id);
            $this->addPdf();
            // add an entry to the json file
            $elabid = 'None';
            if ($this->Entity->type === 'experiments') {
                $elabid = $this->Entity->entityData['elabid'];
            }
            $this->jsonArr[] = array(
                'type' => $this->Entity->type,
                'title' => stripslashes($this->Entity->entityData['title']),
                'body' => stripslashes($this->Entity->entityData['body']),
                'date' => $this->Entity->entityData['date'],
                'elabid' => $elabid,
                'files' => $this->fileArr
            );
            unset($this->fileArr);
        }
    }

    /**
     * Loop on each id and add it to our zip archive
     * This could be called the main function.
     *
     * @throws Exception If the zip failed
     */
    private function loopIdArr()
    {
        $this->idArr = explode(" ", $this->idList);
        foreach ($this->idArr as $id) {
            $this->addToZip($id);
        }
        $this->addJson();
        $this->zip->close();
        // check if it failed for some reason
        if (!is_file($this->filePath)) {
            throw new Exception(_('Error making the zip archive!'));
        }
    }

    /**
     * Clean up the temporary files (csv, txt and pdf)
     *
     */
    public function __destruct()
    {
        foreach ($this->filesToDelete as $file) {
            unlink($file);
        }
    }
}
