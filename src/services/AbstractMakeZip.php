<?php declare(strict_types=1);
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

namespace Elabftw\Services;

use Elabftw\Models\Config;
use Elabftw\Models\Items;
use ZipStream\ZipStream;

/**
 * Mother class of the Make*Zip services
 */
abstract class AbstractMakeZip extends AbstractMake
{
    protected ZipStream $Zip;

    protected string $folder = '';

    abstract public function getZip(): void;

    /**
     * Folder and zip file name begins with date for experiments
     */
    protected function getBaseFileName(): string
    {
        $prefix = 'date';
        // items will show category instead of date as file name prefix
        if ($this->Entity instanceof Items) {
            $prefix = 'category';
        }
        return sprintf('%s - %s', $this->Entity->entityData[$prefix], Filter::forFilesystem($this->Entity->entityData['title']));
    }

    /**
     * Add attached files
     *
     * @param array<array-key, array<string, string>> $filesArr the files array
     */
    protected function addAttachedFiles($filesArr): void
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
    protected function addPdf(): void
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
}
