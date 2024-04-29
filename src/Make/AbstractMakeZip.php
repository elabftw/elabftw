<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Storage;
use Elabftw\Interfaces\PdfMakerInterface;
use Elabftw\Interfaces\ZipMakerInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Templates;
use Elabftw\Services\Filter;
use Elabftw\Services\MpdfProvider;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use ZipStream\ZipStream;

/**
 * Mother class of the Make*Zip services
 */
abstract class AbstractMakeZip extends AbstractMake implements ZipMakerInterface
{
    protected ZipStream $Zip;

    protected bool $usePdfa = false;

    protected string $folder = '';

    protected string $contentType = 'application/zip';

    protected string $extension = '.zip';

    protected string $hashAlgorithm = 'sha256';

    /**
    * Constructor
    *
    * @param AbstractEntity $entity Experiments or Database
    */
    public function __construct(AbstractEntity $entity, protected bool $includeChangelog = false)
    {
        parent::__construct($entity);
    }

    /**
     * Folder and zip file name begins with date for experiments
     */
    protected function getBaseFileName(): string
    {
        // items will show category instead of date as file name prefix
        if ($this->Entity instanceof Items || $this->Entity instanceof ItemsTypes) {
            $prefix = Filter::forFilesystem($this->Entity->entityData['category_title'] ?? '');
        } elseif ($this->Entity instanceof Templates) {
            $prefix = 'Experiment template';
        } else { // Experiments
            $prefix = Filter::forFilesystem($this->Entity->entityData['date']);
        }

        return sprintf(
            '%s - %s - %s',
            $prefix,
            // prevent a zip name with too much characters from the title, see #3966
            substr(Filter::forFilesystem($this->Entity->entityData['title']), 0, 100),
            Tools::getShortElabid($this->Entity->entityData['elabid'] ?? ''),
        );
    }

    /**
     * Add attached files
     *
     * @param array<array-key, array<string, string>> $filesArr the files array
     */
    protected function addAttachedFiles($filesArr): array
    {
        $realNamesSoFar = array();
        $i = 0;
        foreach ($filesArr as &$file) {
            $i++;
            $realName = $file['real_name'];
            // if we have a file with the same name, it shouldn't overwrite the previous one
            if (in_array($realName, $realNamesSoFar, true)) {
                $realName = sprintf('%d_%s', $i, $realName);
            }
            $realNamesSoFar[] = $realName;
            // modify the real_name in place
            $file['real_name'] = $realName;
            $storageFs = Storage::from((int) $file['storage'])->getStorage()->getFs();

            // make sure we have a hash
            if (empty($file['hash'])) {
                $file['hash'] = hash($this->hashAlgorithm, $storageFs->read($file['long_name']));
            }

            // add files to archive
            $this->Zip->addFileFromStream($this->folder . '/' . $realName, $storageFs->readStream($file['long_name']));
        }
        return $filesArr;
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
        return new MakePdf($log, $MpdfProvider, $this->Entity, array($this->Entity->id), $this->includeChangelog);
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
}
