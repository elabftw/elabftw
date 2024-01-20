<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Import;

use Elabftw\Elabftw\FsTools;
use Elabftw\Models\Users;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

/**
 * Mother class for importing zip file
 */
abstract class AbstractZip extends AbstractImport
{
    // path where we extract the archive content (subfolder of cache/elab)
    protected string $tmpPath;

    // the folder name where we extract the archive
    protected string $tmpDir;

    // in version 5.0.0 we switched from filter input to escape output
    // do we need to update old data upon import
    protected bool $switchToEscapeOutput = true;

    protected array $allowedMimes = array(
        'application/zip',
        'application/force-download',
        'application/x-zip-compressed',
    );

    public function __construct(Users $Users, string $target, string $canread, string $canwrite, UploadedFile $UploadedFile, protected FilesystemOperator $fs)
    {
        parent::__construct($Users, $target, $canread, $canwrite, $UploadedFile);
        // set up a temporary directory in the cache to extract the archive to
        $this->tmpDir = FsTools::getUniqueString();
        $this->tmpPath = FsTools::getCacheFolder('elab') . '/' . $this->tmpDir;

        $Zip = new ZipArchive();
        $Zip->open($this->UploadedFile->getPathname());
        $Zip->extractTo($this->tmpPath);
    }

    /**
     * Cleanup: remove the temporary folder created
     */
    public function __destruct()
    {
        $this->fs->deleteDirectory($this->tmpDir);
    }
}
