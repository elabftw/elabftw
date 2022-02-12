<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use function count;
use Elabftw\Elabftw\ContentParams;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use function json_encode;
use ZipStream\Option\Archive as ArchiveOptions;
use ZipStream\ZipStream;

/**
 * Make a zip archive from experiment or db item
 */
class MakeStreamZip extends AbstractMake
{
    private ZipStream $Zip;

    private string $folder = '';

    // array that will be converted to json
    private array $jsonArr = array();

    public function __construct(AbstractEntity $entity, private array $idArr)
    {
        parent::__construct($entity);
        $opt = new ArchiveOptions();
        // crucial option for a stream input
        $opt->setZeroHeader(true);
        $this->Zip = new ZipStream('a.zip', $opt);
    }

    /**
     * Get the name of the generated file
     */
    public function getFileName(): string
    {
        if (count($this->idArr) === 1) {
            $this->Entity->setId((int) $this->idArr[0]);
            $this->Entity->canOrExplode('read');
            return $this->getBaseFileName() . '.zip';
        }
        return 'export.elabftw.zip';
    }

    /**
     * Loop on each id and add it to our zip archive
     * This could be called the main function.
     */
    public function getZip(): void
    {
        foreach ($this->idArr as $id) {
            $this->addToZip((int) $id);
        }

        // add the (hidden) .elabftw.json file useful for reimport
        $this->Zip->addFile('.elabftw.json', json_encode($this->jsonArr, JSON_THROW_ON_ERROR, 512));

        $this->Zip->finish();
    }

    /**
     * Folder and zip file name begins with date for experiments
     */
    private function getBaseFileName(): string
    {
        if ($this->Entity instanceof Experiments) {
            return $this->Entity->entityData['date'] . ' - ' . Filter::forFilesystem($this->Entity->entityData['title']);
        } elseif ($this->Entity instanceof Items) {
            return $this->Entity->entityData['category'] . ' - ' . Filter::forFilesystem($this->Entity->entityData['title']);
        }

        throw new ImproperActionException(sprintf('Entity of type %s is not allowed in this context', $this->Entity::class));
    }

    /**
     * Add attached files
     * TODO code is duplicated in makebackupzip
     *
     * @param array<array-key, array<string, string>> $filesArr the files array
     */
    private function addAttachedFiles($filesArr): void
    {
        $real_names_so_far = array();
        $i = 0;
        $Config = Config::getConfig();
        $storage = (int) $Config->configArr['uploads_storage'];
        $storageFs = (new StorageFactory($storage))->getStorage()->getFs();
        foreach ($filesArr as $file) {
            $i++;
            $realName = $file['real_name'];
            // if we have a file with the same name, it shouldn't overwrite the previous one
            if (in_array($realName, $real_names_so_far, true)) {
                $realName = (string) $i . '_' . $realName;
            }
            $real_names_so_far[] = $realName;

            // add files to archive
            $this->Zip->addFileFromStream($this->folder . '/' . $realName, $storageFs->readStream($file['long_name']));
        }
    }

    /**
     * Add a PDF file to the ZIP archive
     */
    private function addPdf(): void
    {
        $userData = $this->Entity->Users->userData;
        $MpdfProvider = new MpdfProvider(
            $userData['fullname'],
            $userData['pdf_format'],
            (bool) $userData['pdfa'],
        );
        $MakePdf = new MakePdf($MpdfProvider, $this->Entity);
        $this->Zip->addFile($this->folder . '/' . $MakePdf->getFileName(), $MakePdf->getFileContent());
    }

    /**
     * Add a CSV file to the ZIP archive
     *
     * @param int $id The id of the item we are zipping
     */
    private function addCsv(int $id): void
    {
        $MakeCsv = new MakeCsv($this->Entity, array($id));
        $this->Zip->addFile($this->folder . '/' . $this->folder . '.csv', $MakeCsv->getFileContent());
    }

    /**
     * This is where the magic happens
     *
     * @param int $id The id of the item we are zipping
     */
    private function addToZip(int $id): void
    {
        $this->Entity->setId($id);
        $permissions = $this->Entity->getPermissions();
        if ($permissions['read']) {
            $uploadedFilesArr = $this->Entity->Uploads->readAllNormal();
            $entityArr = $this->Entity->entityData;
            // save the uploads in entityArr for the json file
            $entityArr['uploads'] = $uploadedFilesArr;
            // add links
            $entityArr['links'] = $this->Entity->Links->read(new ContentParams());
            // add steps
            $entityArr['steps'] = $this->Entity->Steps->read(new ContentParams());
            $this->folder = $this->getBaseFileName();

            if (!empty($uploadedFilesArr)) {
                $this->addAttachedFiles($uploadedFilesArr);
            }
            $this->addCsv($id);
            $this->addPdf();
            // add an entry to the json file
            $this->jsonArr[] = $entityArr;
        }
    }
}
