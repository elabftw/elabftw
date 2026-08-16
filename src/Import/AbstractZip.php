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
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\Users;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use League\Flysystem\ZipArchive\FilesystemZipArchiveProvider;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;
use ZipArchive;

use function explode;
use function fclose;
use function in_array;
use function iterator_to_array;
use function preg_match;
use function rawurldecode;
use function sprintf;
use function str_contains;
use function str_replace;
use function str_starts_with;

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
        $this->assertArchiveMemberNamesAreSafe();

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
     * Validate original ZIP member names before the Flysystem adapter normalizes them.
     */
    private function assertArchiveMemberNamesAreSafe(): void
    {
        $archive = new ZipArchive();
        if ($archive->open($this->UploadedFile->getPathname(), ZipArchive::RDONLY) !== true) {
            throw new ImproperActionException('The uploaded file is not a valid ZIP archive.');
        }

        try {
            for ($index = 0; $index < $archive->numFiles; $index++) {
                $rawPath = $archive->getNameIndex($index, ZipArchive::FL_UNCHANGED);
                if ($rawPath === false) {
                    throw new ImproperActionException('The archive contains an invalid path.');
                }
                $this->assertArchivePathIsSafe($rawPath);
            }
        } finally {
            $archive->close();
        }
    }

    /**
     * Extract everything from a ZIP-backed Flysystem into another directory
     * on any Flysystem backend (local, S3, etc).
     */
    private function extractZipFilesystemToDir(FilesystemAdapter $zipFs): void
    {
        // Validate the complete archive before writing anything. This prevents a
        // later unsafe member from leaving a partially extracted import behind.
        $items = iterator_to_array($zipFs->listContents('', true), false);
        foreach ($items as $item) {
            $this->assertArchivePathIsSafe($item->path());
        }

        foreach ($items as $item) {
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

    /**
     * Reject paths which can escape the per-import directory on any supported backend.
     */
    private function assertArchivePathIsSafe(string $rawPath): void
    {
        // Decode repeatedly so double-encoded traversal cannot become dangerous
        // if another storage layer decodes the path in the future.
        $decodedPath = $rawPath;
        do {
            $previousPath = $decodedPath;
            $decodedPath = rawurldecode($decodedPath);
        } while ($decodedPath !== $previousPath);

        // ZIP member names use forward slashes. Backslashes are rejected instead
        // of normalized so their meaning cannot vary between storage backends.
        $normalizedPath = str_replace('\\', '/', $decodedPath);
        if (
            $normalizedPath === ''
            || str_contains($decodedPath, "\0")
            || str_contains($decodedPath, '\\')
            || str_starts_with($normalizedPath, '/')
            || preg_match('/^[a-zA-Z]:/', $normalizedPath) === 1
            || in_array('..', explode('/', $normalizedPath), true)
        ) {
            throw new ImproperActionException('The archive contains an unsafe path.');
        }
    }
}
