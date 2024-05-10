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

use Elabftw\Interfaces\PdfMakerInterface;
use Elabftw\Models\AbstractConcreteEntity;
use Elabftw\Services\Filter;
use Elabftw\Services\MpdfProvider;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use ZipStream\ZipStream;

/**
 * Make a zip with only the modified items on a time period
 */
class MakeBackupZip extends AbstractMakeZip
{
    public function __construct(
        protected ZipStream $Zip,
        protected AbstractConcreteEntity $Entity,
        private string $period,
        protected bool $includeChangelog = false,
    ) {
        parent::__construct($Zip);
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
    public function getStreamZip(): void
    {
        // loop on every user
        $usersArr = $this->Entity->Users->readFromQuery('');
        foreach ($usersArr as $user) {
            $idArr = $this->Entity->getIdFromLastchange($user['userid'], $this->period);
            foreach ($idArr as $id) {
                $this->addToZip($id, $user['fullname']);
            }
        }
        $this->Zip->finish();
    }

    // TODO factorize with makestreamzip
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
    // END duplication

    /**
     * This is where the magic happens
     *
     * @param int $id The id of the item we are zipping
     */
    private function addToZip(int $id, string $fullname): void
    {
        // we're making a backup so ignore permissions access
        $this->Entity->bypassReadPermission = true;
        $this->Entity->setId($id);
        $uploadedFilesArr = $this->Entity->entityData['uploads'];
        $this->folder = Filter::forFilesystem($fullname) . '/' . $this->Entity->toFsTitle();

        if (!empty($uploadedFilesArr)) {
            $this->addAttachedFiles($uploadedFilesArr);
        }
        $this->addPdf();
    }
}
