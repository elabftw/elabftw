<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Import;

use Elabftw\Elabftw\FsTools;
use Elabftw\Enums\Storage;
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
    protected string $tmpDir = '';

    protected array $allowedMimes = array(
        'application/zip',
        'application/force-download',
        'application/x-zip-compressed',
    );

    protected FilesystemOperator $tmpFs;

    // in version 5.0.0 we switched from filter input to escape output
    // setting this to true will convert html escaped entities into the correct character
    protected bool $switchToEscapeOutput = false;

    public function __construct(
        Users $requester,
        UploadedFile $UploadedFile,
        protected FilesystemOperator $fs,
    ) {
        parent::__construct($requester, $UploadedFile);
        // set up a temporary directory in the cache to extract the archive to
        $this->tmpDir = FsTools::getUniqueString();
        $cacheStorage = Storage::CACHE->getStorage();
        $this->tmpPath = $cacheStorage->getPath() . '/' . $this->tmpDir;
        $this->tmpFs = $cacheStorage->getFs();

        $Zip = new ZipArchive();
        $Zip->open($this->UploadedFile->getPathname());
        $Zip->extractTo($this->tmpPath);
    }

    /**
     * Cleanup: remove the temporary folder created
     */
    public function __destruct()
    {
        $this->fs->deleteDirectory($this->tmpPath);
    }

    /**
     * subject might needs to be transformed due to the switch from filter input to escape output strategy
     */
    protected function transformIfNecessary(
        string $subject,
        bool $isComment = false,
    ): string {
        // skip transformation
        if (!$this->switchToEscapeOutput || $subject === '') {
            return $subject;
        }

        $search = array('&#34;', '&#39;');
        $replace = array('"', '\'');

        if ($isComment) {
            $search[] = '<br />';
            $replace[] = '';
        }

        return str_replace(
            $search,
            $replace,
            $subject,
        );
    }
}
