<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Hash;

use Aws\HashingStream;
use Aws\PhpHash;
use GuzzleHttp\Psr7\StreamWrapper;
use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use Override;
use RuntimeException;

use function bin2hex;
use function is_resource;

final class StreamHasher extends AbstractHash
{
    private HashingStream $hashingStream;

    /** @var resource */
    private mixed $resource;

    /**
     * @param resource $inputStream
     */
    public function __construct($inputStream)
    {
        if (!is_resource($inputStream)) {
            throw new InvalidArgumentException('Expected a stream resource.');
        }

        $stream = Utils::streamFor($inputStream);

        $this->hashingStream = new HashingStream(
            $stream,
            new PhpHash($this->getAlgo()),
            function (string $hash): void {
                // HashingStream can invoke the callback more than once when the
                // stream is read again at EOF. Keep the first completed digest.
                $this->hash ??= bin2hex($hash);
            },
        );

        // Convert the PSR-7 hashing stream back into a PHP resource so it can
        // be passed to Flysystem::writeStream().
        //
        // This also means AWS sees a user-space stream instead of a plainfile,
        // so multipart uploads consume this stream instead of reopening the
        // original file path for each part.
        $this->resource = StreamWrapper::getResource($this->hashingStream);
    }

    /**
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    #[Override]
    protected function compute(): string
    {
        if ($this->hash === null) {
            throw new RuntimeException(
                'Stream was not completely read before requesting its hash.',
            );
        }

        return $this->hash;
    }
}
