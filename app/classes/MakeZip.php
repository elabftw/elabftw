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
class MakeZip extends AbstractMake
{
    /** @var ZipArchive $Zip the Zip object */
    private $Zip;

    /** @var string $idList the input ids */
    private $idList;

    /** @var array $idArr the input ids but in an array */
    private $idArr = array();

    /** @var array $trash files to be deleted by destructor */
    private $trash = array();

    /** @var string $cleanTitle a formatted title */
    private $cleanTitle;

    /** @var string $fileName a sha512 sum */
    public $fileName;

    /** @var string $filePath full path of file */
    public $filePath;

    /** @var string $folder name of folder */
    private $folder;

    /** @var array $jsonArr array that will be converted to json */
    private $jsonArr = array();

    /**
     * Give me an id list and a type, I make good zip for you
     *
     * @param AbstractEntity $entity
     * @param string $idList 1+3+5+8
     * @throws Exception if we don't have ZipArchive extension
     */
    public function __construct(AbstractEntity $entity, $idList)
    {
        parent::__construct($entity);

        // we check first if the zip extension is here
        if (!class_exists('ZipArchive')) {
            throw new Exception(
                _("You are missing the ZipArchive class in php. Uncomment the line extension=zip.so in php.ini file.")
            );
        }

        $this->idList = $idList;

        $this->fileName = $this->getUniqueString();
        $this->filePath = $this->getFilePath($this->fileName, true);

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
        $this->Zip = new ZipArchive;

        if (!$this->Zip->open($this->filePath, ZipArchive::CREATE)) {
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
        $this->cleanTitle = preg_replace(
            '/[^A-Za-z0-9]/',
            '_',
            htmlspecialchars_decode($this->Entity->entityData['title'], ENT_QUOTES)
        );
    }

    /**
     * Add the .asn1 token and the timestamped pdf to the zip archive
     *
     * @param int $id The id of current item we are zipping
     */
    private function addTimestampFiles($id)
    {
        if ($this->Entity instanceof Experiments && $this->Entity->entityData['timestamped']) {
            // SQL to get the path of the token
            $sql = "SELECT real_name, long_name FROM uploads WHERE item_id = :id AND (
                type = 'timestamp-token'
                OR type = 'exp-pdf-timestamp') LIMIT 2";
            $req = $this->Db->prepare($sql);
            $req->bindParam(':id', $id);
            $req->execute();
            $uploads = $req->fetchAll();
            foreach ($uploads as $upload) {
                // add it to the .zip
                $this->Zip->addFile(
                    ELAB_ROOT . 'uploads/' . $upload['long_name'],
                    $this->folder . "/" . $upload['real_name']
                );
            }
        }
    }

    /**
     * Folder begins with date for experiments
     *
     */
    private function nameFolder()
    {
        if ($this->Entity instanceof Experiments) {
            $this->folder = $this->Entity->entityData['date'] . "-" . $this->cleanTitle;
        } else { // items
            $this->folder = $this->Entity->entityData['category'] . " - " . $this->cleanTitle;
        }
    }

    /**
     * Add attached files
     *
     * @param array $filesArr the files array
     */
    private function addAttachedFiles($filesArr)
    {
        $real_names_so_far = array();
        $i = 0;
        foreach ($filesArr as $file) {
            $i++;
            $realName = $file['real_name'];
            // if we have a file with the same name, it shouldn't overwrite the previous one
            if (in_array($realName, $real_names_so_far)) {
                $realName = $i . '_' . $realName;
            }
            $real_names_so_far[] = $realName;

            // add files to archive
            $this->Zip->addFile(ELAB_ROOT . 'uploads/' . $file['long_name'], $this->folder . "/" . $realName);
        }
    }

    /**
     * Add a PDF file to the ZIP archive
     *
     */
    private function addPdf()
    {
        $MakePdf = new MakePdf($this->Entity);
        $MakePdf->output(true);
        $this->Zip->addFile($MakePdf->filePath, $this->folder . '/' . $MakePdf->getCleanName());
        $this->trash[] = $MakePdf->filePath;
    }

    /**
     * Add a CSV file to the ZIP archive
     *
     * @param int $id The id of the item we are zipping
     */
    private function addCsv($id)
    {
        $MakeCsv = new MakeCsv($this->Entity, $id);
        $this->Zip->addFile($MakeCsv->filePath, $this->folder . "/" . $this->cleanTitle . ".csv");
        $this->trash[] = $MakeCsv->filePath;
    }

    /**
     * This is where the magic happens
     *
     * @param int $id The id of the item we are zipping
     */
    private function addToZip($id)
    {
        $this->Entity->setId($id);
        $permissions = $this->Entity->getPermissions();
        $this->setCleanTitle();
        if ($permissions['read']) {
            $uploadedFilesArr = $this->Entity->Uploads->readAll();
            $entityArr = $this->Entity->entityData;
            $entityArr['uploads'] = $uploadedFilesArr;

            $this->nameFolder();
            $this->addTimestampFiles($id);
            if (is_array($entityArr)) {
                $this->addAttachedFiles($entityArr['uploads']);
            }
            $this->addCsv($id);
            $this->addPdf();
            // add an entry to the json file
            $this->jsonArr[] = $entityArr;
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

        // add the (hidden) .elabftw.json file useful for reimport
        $this->Zip->addFromString(".elabftw.json", json_encode($this->jsonArr));

        $this->Zip->close();
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
        foreach ($this->trash as $file) {
            unlink($file);
        }
    }
}
