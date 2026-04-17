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

use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Storage;
use Elabftw\Models\Users\Users;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use League\Flysystem\ZipArchive\FilesystemZipArchiveProvider;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;

use function fclose;
use function sprintf;
use function str_replace;

/**
 * Mother class for importing zip file
 */
abstract class AbstractZip extends AbstractImport
{
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
        protected LoggerInterface $logger,
    ) {
        parent::__construct($requester, $UploadedFile, $logger);
        // we extract everything into a temporary directory
        $this->tmpDir = Tools::getUuidv4();
        $this->emitLog(sprintf('temporary directory: %s', $this->tmpDir), LogLevel::DEBUG);
        // we use the Exports storage to store decompressed data
        $this->tmpFs = Storage::EXPORTS->getStorage()->getFs();

        $adapter = new ZipArchiveAdapter(
            new FilesystemZipArchiveProvider($this->UploadedFile->getPathname())
        );
        $this->extractZipFilesystemToDir($adapter);
    }

    /**
     * Cleanup: remove the temporary folder created
     */
    public function __destruct()
    {
        if ($this->tmpDir === '') {
            return;
        }
        $this->tmpFs->deleteDirectory($this->tmpDir);
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

    /**
     * Extract everything from a ZIP-backed Flysystem into another directory
     * on any Flysystem backend (local, S3, etc).
     */
    private function extractZipFilesystemToDir(FilesystemAdapter $zipFs): void
    {
        foreach ($zipFs->listContents('', true) as $item) {

            $rawPath = $item->path();
            $this->emitLog(sprintf('ZIP: extracting: %s', $rawPath), LogLevel::DEBUG);

            // fix eln v < 106 with duplicated / in path for uploaded files
            $targetPath = $this->tmpDir . '/' . str_replace('//', '/', $rawPath);

            if ($item->isDir()) {
                $this->emitLog(sprintf('ZIP: creating directory %s', $targetPath), LogLevel::DEBUG);
                $this->tmpFs->createDirectory($targetPath);
                continue;
            }

            $stream = $zipFs->readStream($rawPath);

            if ($stream === false) {
                throw new RuntimeException(sprintf('Failed to read stream for: %s', $rawPath));
            }

            try {
                $this->tmpFs->writeStream($targetPath, $stream);
            } finally {
                fclose($stream);
            }
        }
    }
}
