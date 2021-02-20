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

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use PDO;
use ZipStream\Option\Archive as ArchiveOptions;
use ZipStream\ZipStream;

/**
 * Make a zip with only the modified items on a time period
 */
class MakeBackupZip extends AbstractMake
{
    private ZipStream $Zip;

    private string $period = '15000101-30000101';

    // files to be deleted by destructor
    private array $trash = array();

    private string $folder = '';

    /**
     * Give me a time period, I make good zip for you
     */
    public function __construct(AbstractEntity $entity, string $period)
    {
        parent::__construct($entity);

        // we check first if the zip extension is here
        if (!class_exists('ZipArchive')) {
            throw new ImproperActionException('Fatal error! Missing extension: php-zip. Make sure it is installed and activated.');
        }

        $opt = new ArchiveOptions();
        $opt->setFlushOutput(true);
        $this->Zip = new ZipStream(null, $opt);

        $this->period = $period;
    }

    /**
     * Clean up the temporary files (csv and pdf)
     */
    public function __destruct()
    {
        foreach ($this->trash as $file) {
            unlink($file);
        }
    }

    /**
     * Get the name of the generated file
     */
    public function getFileName(): string
    {
        return 'export.elabftw.zip';
    }

    /**
     * Loop on each id and add it to our zip archive
     * This could be called the main function.
     */
    public function getZip(): void
    {
        // loop on every user
        $usersArr = $this->Entity->Users->readFromQuery('');
        foreach ($usersArr as $user) {
            $idArr = $this->Entity->getIdFromLastchange((int) $user['userid'], $this->period);
            foreach ($idArr as $id) {
                $this->addToZip((int) $id, $user['fullname']);
            }
        }
        $this->Zip->finish();
    }

    /**
     * Add the .asn1 token and the timestamped pdf to the zip archive
     *
     * @param int $id The id of current item we are zipping
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
            if ($uploads === false) {
                $uploads = array();
            }
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
     * Folder and zip file name begins with date for experiments
     */
    private function getBaseFileName(): string
    {
        if ($this->Entity instanceof Experiments) {
            return $this->Entity->entityData['date'] . ' - ' . Filter::forFilesystem($this->Entity->entityData['title']);
        } elseif ($this->Entity instanceof Database) {
            return $this->Entity->entityData['category'] . ' - ' . Filter::forFilesystem($this->Entity->entityData['title']);
        }

        throw new ImproperActionException(sprintf('Entity of type %s is not allowed in this context', get_class($this->Entity)));
    }

    /**
     * Add attached files
     *
     * @param array<array-key, array<string, string>> $filesArr the files array
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
                $realName = (string) $i . '_' . $realName;
            }
            $real_names_so_far[] = $realName;

            // add files to archive
            $this->Zip->addFileFromPath($this->folder . '/' . $realName, $this->getUploadsPath() . $file['long_name']);
        }
    }

    /**
     * Add a PDF file to the ZIP archive
     */
    private function addPdf(): void
    {
        $MakePdf = new MakePdf($this->Entity, true);
        $MakePdf->outputToFile();
        $this->Zip->addFileFromPath($this->folder . '/' . $MakePdf->getFileName(), $MakePdf->filePath);
        $this->trash[] = $MakePdf->filePath;
    }

    /**
     * This is where the magic happens
     *
     * @param int $id The id of the item we are zipping
     */
    private function addToZip(int $id, string $fullname): void
    {
        // we're making a backup so ignore permissions access
        $this->Entity->bypassPermissions = true;
        $this->Entity->setId($id);
        $this->Entity->populate();
        $uploadedFilesArr = $this->Entity->Uploads->readAll();
        $this->folder = Filter::forFilesystem($fullname) . '/' . $this->getBaseFileName();

        $this->addTimestampFiles($id);
        if (!empty($uploadedFilesArr)) {
            $this->addAttachedFiles($uploadedFilesArr);
        }
        $this->addPdf();
    }
}
