<?php
/**
 * \Elabftw\Elabftw\MakeStreamZip
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Exception;
use PDO;
use ZipStream\ZipStream;

/**
 * Make a zip archive from experiment or db item
 */
class MakeStreamZip extends AbstractMake
{
    /** @var ZipStream $Zip the ZipStream object */
    private $Zip;

    /** @var string $idList the input ids */
    private $idList;

    /** @var array $idArr the input ids but in an array */
    private $idArr = array();

    /** @var array $trash files to be deleted by destructor */
    private $trash = array();

    /** @var string $cleanTitle a formatted title */
    private $cleanTitle;

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
     * @return void
     */
    public function __construct(AbstractEntity $entity, $idList)
    {
        parent::__construct($entity);

        // we check first if the zip extension is here
        if (!class_exists('ZipArchive')) {
            throw new Exception('Fatal error! Missing extension: php-zip. Make sure it is installed and activated.');
        }

        $this->Zip = new ZipStream('elabftw-export.zip');

        $this->idList = $idList;
    }

    public function output()
    {
        $this->loopIdArr();
    }

    /**
     * Make a title without special char for folder inside .zip
     *
     * @return void
     */
    private function setCleanTitle(): void
    {
        $this->cleanTitle = preg_replace(
            '/[^A-Za-z0-9 ]/',
            '_',
            htmlspecialchars_decode($this->Entity->entityData['title'], ENT_QUOTES)
        );
    }

    /**
     * Add the .asn1 token and the timestamped pdf to the zip archive
     *
     * @param int $id The id of current item we are zipping
     * @return void
     */
    private function addTimestampFiles(int $id): void
    {
        if ($this->Entity instanceof Experiments && $this->Entity->entityData['timestamped']) {
            // SQL to get the path of the token
            $sql = "SELECT real_name, long_name FROM uploads WHERE item_id = :id AND (
                type = 'timestamp-token'
                OR type = 'exp-pdf-timestamp') LIMIT 2";
            $req = $this->Db->prepare($sql);
            $req->bindParam(':id', $id, PDO::PARAM_INT);
            $req->execute();
            $uploads = $req->fetchAll();
            foreach ($uploads as $upload) {
                // add it to the .zip
                $this->Zip->addFileFromPath(
                    $this->folder . '/' . $upload['real_name'],
                    $this->getUploadsPath() . $upload['long_name']
                );
            }
        }
    }

    /**
     * Folder begins with date for experiments
     *
     * @return void
     */
    private function nameFolder(): void
    {
        if ($this->Entity instanceof Experiments) {
            $this->folder = $this->Entity->entityData['date'] . " - " . $this->cleanTitle;
        } elseif ($this->Entity instanceof Database) {
            $this->folder = $this->Entity->entityData['category'] . " - " . $this->cleanTitle;
        }
    }

    /**
     * Add attached files
     *
     * @param array $filesArr the files array
     * @return void
     */
    private function addAttachedFiles($filesArr): void
    {
        $real_names_so_far = array();
        $i = 0;
        foreach ($filesArr as $file) {
            $i++;
            $realName = $file['real_name'];
            // if we have a file with the same name, it shouldn't overwrite the previous one
            if (in_array($realName, $real_names_so_far, true)) {
                $realName = $i . '_' . $realName;
            }
            $real_names_so_far[] = $realName;

            // add files to archive
            $this->Zip->addFileFromPath($this->folder . '/' . $realName, $this->getUploadsPath() . $file['long_name']);
        }
    }

    /**
     * Add a PDF file to the ZIP archive
     *
     * @return void
     */
    private function addPdf(): void
    {
        $MakePdf = new MakePdf($this->Entity, true);
        $MakePdf->outputToFile();
        $this->Zip->addFileFromPath($this->folder . '/' . $MakePdf->getCleanName(), $MakePdf->filePath);
        $this->trash[] = $MakePdf->filePath;
    }

    /**
     * Add a CSV file to the ZIP archive
     *
     * @param int $id The id of the item we are zipping
     * @return void
     */
    private function addCsv(int $id): void
    {
        $MakeCsv = new MakeCsv($this->Entity, (string) $id);
        $this->Zip->addFileFromPath($this->folder . '/' . $this->cleanTitle . '.csv', $MakeCsv->filePath);
        $this->trash[] = $MakeCsv->filePath;
    }

    /**
     * This is where the magic happens
     *
     * @param int $id The id of the item we are zipping
     * @return void
     */
    private function addToZip(int $id): void
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
            if (!empty($uploadedFilesArr)) {
                $this->addAttachedFiles($uploadedFilesArr);
            }
            $this->addCsv($id);
            $this->addPdf();
            // add an entry to the json file
            $this->jsonArr[] = $entityArr;
        }
    }

    public function getCleanName(): string
    {
        return 'elabftw-export.zip';
    }

    /**
     * Loop on each id and add it to our zip archive
     * This could be called the main function.
     *
     * @throws Exception If the zip failed
     * @return void
     */
    private function loopIdArr(): void
    {
        $this->idArr = explode(" ", $this->idList);
        foreach ($this->idArr as $id) {
            $this->addToZip((int) $id);
        }

        // add the (hidden) .elabftw.json file useful for reimport
        $this->Zip->addFile(".elabftw.json", (string) json_encode($this->jsonArr));

        $this->Zip->finish();
    }

    /**
     * Clean up the temporary files (csv and pdf)
     *
     * @return void
     */
    public function __destruct()
    {
        foreach ($this->trash as $file) {
            unlink($file);
        }
    }
}
