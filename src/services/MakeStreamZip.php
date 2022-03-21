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
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use function json_encode;
use League\Flysystem\UnableToReadFile;
use ZipStream\ZipStream;

/**
 * Make a zip archive from experiment or db item
 */
class MakeStreamZip extends AbstractMakeZip
{
    // array that will be converted to json
    private array $jsonArr = array();

    public function __construct(protected ZipStream $Zip, Experiments | Items $entity, private array $idArr)
    {
        parent::__construct($entity);
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
     * Note the different try catch blocks to skip issues that would stop the zip generation
     *
     * @param int $id The id of the item we are zipping
     */
    private function addToZip(int $id): void
    {
        $this->Entity->setId($id);
        try {
            $permissions = $this->Entity->getPermissions();
        } catch (IllegalActionException $e) {
            return;
        }
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
                try {
                    $this->addAttachedFiles($uploadedFilesArr);
                } catch (UnableToReadFile $e) {
                    return;
                }
            }
            $this->addCsv($id);
            $this->addPdf();
            // add an entry to the json file
            $this->jsonArr[] = $entityArr;
        }
    }
}
