<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use DateTimeImmutable;
use Elabftw\Elabftw\App;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Models\AbstractEntity;
use League\Flysystem\UnableToReadFile;
use Elabftw\Services\MpdfProvider;
use Elabftw\Interfaces\PdfMakerInterface;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use ZipStream\ZipStream;

use function count;
use function json_encode;

/**
 * Make a zip archive from experiment or db item
 */
class MakeStreamZip extends AbstractMakeZip
{
    // data array (entries) that will be converted to json
    protected array $dataArr = array();

    public function __construct(
        protected ZipStream $Zip,
        protected AbstractEntity $Entity,
        protected array $idArr,
        protected bool $usePdfa = false,
        protected bool $includeChangelog = false,
    ) {
        parent::__construct($Zip);
    }

    /**
     * Get the name of the generated file
     */
    public function getFileName(): string
    {
        if (count($this->idArr) === 1) {
            $this->Entity->setId($this->idArr[0]);
            $this->Entity->canOrExplode('read');
            return $this->Entity->toFsTitle() . $this->extension;
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
        $this->Zip->addFile('.elabftw.json', json_encode(array('data' => $this->dataArr, 'meta' => $this->getMeta()), JSON_THROW_ON_ERROR, 512));

        $this->Zip->finish();
    }

    protected function getPdf(): PdfMakerInterface
    {
        $userData = $this->Entity->Users->userData;
        $MpdfProvider = new MpdfProvider(
            $userData['fullname'],
            $userData['pdf_format'],
            $this->usePdfa,
        );
        $log = (new Logger('elabftw'))->pushHandler(new ErrorLogHandler());
        return new MakePdf(
            log: $log,
            mpdfProvider: $MpdfProvider,
            requester: $this->Entity->Users,
            entityType: $this->Entity->entityType,
            entityIdArr: array($this->Entity->id),
            includeChangelog: $this->includeChangelog,
        );
    }

    /**
     * Add a PDF file to the ZIP archive
     */
    protected function addPdf(): void
    {
        $MakePdf = $this->getPdf();
        // disable makepdf notifications because they are handled by calling class
        $MakePdf->setNotifications(false);
        $this->Zip->addFile($this->folder . '/' . $MakePdf->getFileName(), $MakePdf->getFileContent());
    }

    /**
     * Produce metadata for the "meta" key of the json file
     */
    protected function getMeta(): array
    {
        $creationDateTime = new DateTimeImmutable();
        return array(
            'elabftw_producer_version' => App::INSTALLED_VERSION,
            'elabftw_producer_version_int' => App::INSTALLED_VERSION_INT,
            'dateCreated' => $creationDateTime->format(DateTimeImmutable::ATOM),
            'count' => count($this->dataArr),
        );
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
            $this->folder = $this->Entity->toFsTitle();

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
            $this->dataArr[] = $entityArr;
        }
    }
}
