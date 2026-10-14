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
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;
use Throwable;
use ZipArchive;

use function explode;
use function in_array;
use function max;
use function preg_match;
use function rawurldecode;
use function sprintf;
use function str_contains;
use function str_ends_with;
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

    protected ZipLimits $limits;

    // in version 5.0.0 we switched from filter input to escape output
    // setting this to true will convert html escaped entities into the correct character
    protected bool $switchToEscapeOutput = false;

    private bool $prepared = false;

    public function __construct(
        Users $requester,
        UploadedFile $UploadedFile,
        protected FilesystemOperator $tmpFs,
        protected LoggerInterface $logger,
    ) {
        parent::__construct($requester, $UploadedFile, $logger);
        $this->limits = ZipLimits::fromEnvironment();
    }

    /**
     * Cleanup: remove the temporary folder created
     */
    public function __destruct()
    {
        $this->cleanup();
    }

    /**
     * Constructors never extract data: failed construction cannot leave files.
     */
    final public function prepare(): void
    {
        if ($this->prepared) {
            return;
        }
        $archive = new ZipArchive();
        if ($archive->open($this->UploadedFile->getPathname(), ZipArchive::RDONLY) !== true) {
            throw new ImproperActionException('The uploaded file is not a valid ZIP archive.');
        }
        try {
            $this->assertArchiveIsSafe($archive);
            // Reject invalid metadata before extracting any attachments.
            $this->readArchiveMetadata($archive);
            $this->tmpDir = Tools::getUuidv4();
            $this->emitLog(sprintf('temporary directory: %s', $this->tmpDir), LogLevel::DEBUG);
            $this->tmpFs->createDirectory($this->tmpDir);
            $this->extractArchive($archive);
            $this->prepared = true;
        } finally {
            $archive->close();
            if (!$this->prepared) {
                $this->cleanup();
            }
        }
    }

    /**
     * Explicit, idempotent cleanup, also used when preparation or import fails.
     */
    final public function cleanup(): void
    {
        if ($this->tmpDir === '') {
            return;
        }
        try {
            $this->tmpFs->deleteDirectory($this->tmpDir);
            $this->tmpDir = '';
            $this->prepared = false;
        } catch (Throwable $e) {
            $this->emitLog(
                sprintf(
                    'Could not delete temporary import directory %s: %s',
                    $this->tmpDir,
                    $e->getMessage(),
                ),
                LogLevel::ERROR,
            );
        }
    }

    abstract protected function readArchiveMetadata(ZipArchive $archive): void;

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
     * Check the complete archive before writing anything. ZIP sizes are only
     * an early rejection check; extraction also checks actual stream bytes.
     */
    private function assertArchiveIsSafe(ZipArchive $archive): void
    {
        if ($archive->numFiles > $this->limits->maxMembers) {
            throw new ImproperActionException('The archive exceeds the member count limit.');
        }
        $totalBytes = 0;
        for ($index = 0; $index < $archive->numFiles; $index++) {
            $stat = $archive->statIndex($index, ZipArchive::FL_UNCHANGED);
            if ($stat === false || $stat['size'] < 0 || $stat['comp_size'] < 0) {
                throw new ImproperActionException('The archive contains invalid member metadata.');
            }
            $this->assertArchivePathIsSafe($stat['name']);
            if ($stat['size'] > $this->limits->maxMemberBytes) {
                throw new ImproperActionException('An archive member exceeds the uncompressed size limit.');
            }
            // Subtract instead of summing unchecked ZIP64 values.
            if ($stat['size'] > $this->limits->maxTotalBytes - $totalBytes) {
                throw new ImproperActionException('The archive exceeds the total uncompressed size limit.');
            }
            $totalBytes += $stat['size'];
            if ($stat['size'] / max(1, $stat['comp_size']) > $this->limits->maxCompressionRatio) {
                throw new ImproperActionException('An archive member exceeds the compression ratio limit.');
            }
        }
    }

    /**
     * Use raw ZIP streams: the ZIP Flysystem adapter's readStream() first
     * decompresses the entire member into an unbounded php://temp stream.
     */
    private function extractArchive(ZipArchive $archive): void
    {
        $totalBytes = 0;
        for ($index = 0; $index < $archive->numFiles; $index++) {
            $rawPath = $archive->getNameIndex($index, ZipArchive::FL_UNCHANGED);
            if ($rawPath === false) {
                throw new ImproperActionException('The archive contains an invalid path.');
            }
            $this->emitLog(sprintf('ZIP: extracting: %s', $rawPath), LogLevel::DEBUG);

            // fix eln v < 106 with duplicated / in path for uploaded files
            $targetPath = $this->tmpDir . '/' . str_replace('//', '/', $rawPath);

            if (str_ends_with($rawPath, '/')) {
                $this->emitLog(sprintf('ZIP: creating directory %s', $targetPath), LogLevel::DEBUG);
                $this->tmpFs->createDirectory($targetPath);
                continue;
            }

            $input = $archive->getStreamIndex($index, ZipArchive::FL_UNCHANGED);
            if ($input === false) {
                throw new RuntimeException(sprintf('Failed to read stream for: %s', $rawPath));
            }
            $limited = new LimitedReadStream(
                $input,
                $this->limits->maxMemberBytes,
                function (int $bytes) use (&$totalBytes): void {
                    if ($bytes > $this->limits->maxTotalBytes - $totalBytes) {
                        throw new ImproperActionException('The archive exceeds the total uncompressed size limit.');
                    }
                    $totalBytes += $bytes;
                },
            );
            try {
                $this->tmpFs->writeStream($targetPath, $limited->getResource());
                $limited->assertFullyRead();
            } catch (Throwable $e) {
                // An adapter can wrap a read failure in a write exception.
                $limited->assertNoFailure();
                throw $e;
            } finally {
                $limited->close();
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
