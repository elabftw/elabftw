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

use Elabftw\Enums\Storage;
use Elabftw\Interfaces\ZipMakerInterface;
use ZipStream\ZipStream;
use Override;

/**
 * Mother class of the Make*Zip services
 */
abstract class AbstractMakeZip extends AbstractMake implements ZipMakerInterface
{
    protected bool $usePdfa = false;

    protected string $folder = '';

    protected string $contentType = 'application/zip';

    protected string $extension = '.zip';

    protected string $hashAlgorithm = 'sha256';

    protected bool $bypassReadPermission = false;

    public function __construct(protected ZipStream $Zip)
    {
        parent::__construct();
    }

    #[Override]
    public function getFileContent(): string
    {
        return '';
    }

    /**
     * @param resource $stream
     */
    protected function addAttachedFileInZip(string $path, mixed $stream): void
    {
        $this->Zip->addFileFromStream($path, $stream);
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
            $storageFs = Storage::from($file['storage'])->getStorage()->getFs();

            // make sure we have a hash
            if (empty($file['hash'])) {
                $file['hash'] = hash($this->hashAlgorithm, $storageFs->read($file['long_name']));
            }

            // add files to archive
            $this->addAttachedFileInZip($this->folder . '/' . $realName, $storageFs->readStream($file['long_name']));
        }
        return $filesArr;
    }
}
