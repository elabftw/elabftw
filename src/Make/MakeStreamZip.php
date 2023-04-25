<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

use function count;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Models\AbstractEntity;
use function json_encode;
use League\Flysystem\UnableToReadFile;
use ZipStream\ZipStream;

/**
 * Make a zip archive from experiment or db item
 */
class MakeStreamZip extends AbstractMakeZip
{
    // array that will be converted to json
    protected array $jsonArr = array();

    public function __construct(protected ZipStream $Zip, AbstractEntity $entity, protected array $idArr, protected bool $usePdfa = false)
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
            return $this->getBaseFileName() . $this->extension;
        }
        return 'export.elabftw' . $this->extension;
    }

    /**
     * Loop on each id and add it to our zip archive
     * This could be called the main function.
     */
    public function getStreamZip(): void
    {
        foreach ($this->idArr as $id) {
            try {
                $this->addToZip((int) $id);
            } catch (IllegalActionException) {
                continue;
            }
        }

        // add the (hidden) .elabftw.json file useful for reimport
        $this->Zip->addFile('.elabftw.json', json_encode($this->jsonArr, JSON_THROW_ON_ERROR, 512));

        $this->Zip->finish();
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
        } catch (IllegalActionException) {
            return;
        }
        if ($permissions['read']) {
            $entityArr = $this->Entity->entityData;
            $uploadedFilesArr = $entityArr['uploads'];
            $this->folder = $this->getBaseFileName();

            if (!empty($uploadedFilesArr)) {
                try {
                    // we overwrite the uploads array with what the function returns so we have correct real_names
                    $entityArr['uploads'] = $this->addAttachedFiles($uploadedFilesArr);
                } catch (UnableToReadFile) {
                    return;
                }
            }
            $this->addPdf();
            // add an entry to the json file
            $this->jsonArr[] = $entityArr;
        }
    }
}
