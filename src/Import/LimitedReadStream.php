<?php

declare(strict_types=1);

namespace Elabftw\Import;

use Closure;
use Elabftw\Exceptions\ImproperActionException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

use function fclose;
use function feof;
use function in_array;
use function is_resource;
use function stream_filter_append;
use function stream_filter_register;
use function stream_get_contents;
use function stream_get_filters;

use const STREAM_FILTER_READ;

/**
 * Limit native decompression reads before storage consumes them. Keeping the
 * original ZIP resource preserves its non-seekable behavior for S3 uploads.
 */
final class LimitedReadStream
{
    private const string FILTER_NAME = 'elabftw.import-limit';

    private int $bytesRead = 0;

    private ?Throwable $failure = null;

    /** @var resource|null */
    private mixed $stream = null;

    /**
     * @param resource $stream
     * @param Closure(int): void|null $onRead
     */
    public function __construct(mixed $stream, private readonly int $maxBytes, private readonly ?Closure $onRead = null)
    {
        if (!is_resource($stream) || $maxBytes < 1) {
            throw new InvalidArgumentException('Expected a stream resource and a positive size limit.');
        }
        $this->stream = $stream;
        if (
            !in_array(self::FILTER_NAME, stream_get_filters(), true)
            && !stream_filter_register(self::FILTER_NAME, ReadLimitFilter::class)
        ) {
            $this->close();
            throw new RuntimeException('Could not register the import stream limit.');
        }
        if (stream_filter_append($stream, self::FILTER_NAME, STREAM_FILTER_READ, $this) === false) {
            fclose($stream);
            throw new RuntimeException('Could not attach the import stream limit.');
        }
    }

    /** @return resource */
    public function getResource()
    {
        if (!is_resource($this->stream)) {
            throw new RuntimeException('The import stream is closed.');
        }
        return $this->stream;
    }

    public function getContents(): string
    {
        $this->assertNoFailure();
        $contents = stream_get_contents($this->getResource());
        $this->assertNoFailure();
        if ($contents === false) {
            throw new RuntimeException('Could not read the decompressed archive member.');
        }
        return $contents;
    }

    public function consume(int $bytes): void
    {
        $this->assertNoFailure();
        try {
            if ($bytes > $this->maxBytes - $this->bytesRead) {
                throw new ImproperActionException('An archive member exceeds the uncompressed size limit.');
            }
            if ($this->onRead !== null) {
                ($this->onRead)($bytes);
            }
            $this->bytesRead += $bytes;
        } catch (Throwable $e) {
            $this->failure = $e;
            throw $e;
        }
    }

    public function assertNoFailure(): void
    {
        if ($this->failure !== null) {
            throw $this->failure;
        }
    }

    public function assertFullyRead(): void
    {
        $this->assertNoFailure();
        if (!feof($this->getResource())) {
            throw new RuntimeException('The decompressed archive member was not completely read.');
        }
    }

    public function close(): void
    {
        $stream = $this->stream;
        $this->stream = null;

        if (is_resource($stream)) {
            fclose($stream);
        }
    }
}
