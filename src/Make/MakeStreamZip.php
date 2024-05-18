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
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Models\AbstractEntity;
use League\Flysystem\UnableToReadFile;
use Elabftw\Services\MpdfProvider;
use Elabftw\Interfaces\PdfMakerInterface;
use Elabftw\Models\Users;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use ZipStream\ZipStream;

use function json_encode;

/**
 * Make a zip archive from experiment or db item
 */
class MakeStreamZip extends AbstractMakeZip
{
    public function __construct(
        protected ZipStream $Zip,
        protected Users $requester,
        protected array $entitySlugs,
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
        return 'export.elabftw' . $this->extension;
    }

    /**
     * Loop on each id and add it to our zip archive
     * This could be called the main function.
     */
    public function getStreamZip(): void
    {
        foreach ($this->entitySlugs as $slug) {
            try {
                $entity = $slug->type->toInstance($this->requester, $slug->id, $this->bypassReadPermission);
            } catch (IllegalActionException | ResourceNotFoundException) {
                continue;
            }
            $this->addToZip($entity);
        }
        $this->Zip->finish();
    }

    protected function getPdf(AbstractEntity $entity): PdfMakerInterface
    {
        $userData = $this->requester->userData;
        $MpdfProvider = new MpdfProvider(
            $userData['fullname'],
            $userData['pdf_format'],
            $this->usePdfa,
        );
        $log = (new Logger('elabftw'))->pushHandler(new ErrorLogHandler());
        return new MakePdf(
            log: $log,
            mpdfProvider: $MpdfProvider,
            requester: $this->requester,
            entityType: $entity->entityType,
            entityIdArr: array($entity->id),
            includeChangelog: $this->includeChangelog,
        );
    }

    /**
     * Add a PDF file to the ZIP archive
     */
    protected function addPdf(AbstractEntity $entity): void
    {
        $MakePdf = $this->getPdf($entity);
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
        );
    }

    private function addToZip(AbstractEntity $entity): void
    {
        $entityArr = $entity->entityData;
        $uploadedFilesArr = $entityArr['uploads'];
        $this->folder = $entity->toFsTitle();

        if (!empty($uploadedFilesArr)) {
            try {
                // we overwrite the uploads array with what the function returns so we have correct real_names
                $entityArr['uploads'] = $this->addAttachedFiles($uploadedFilesArr);
            } catch (UnableToReadFile) {
                return;
            }
        }
        $this->addPdf($entity);
        // add a full json export too
        $JsonMaker = new MakeFullJson($entity, array($entity->id));
        $this->Zip->addFile(
            $this->folder . '/' . $JsonMaker->getFileName(),
            json_encode(array('data' => $JsonMaker->getJsonContent(), 'meta' => $this->getMeta()), JSON_THROW_ON_ERROR, 512),
        );
    }
}
