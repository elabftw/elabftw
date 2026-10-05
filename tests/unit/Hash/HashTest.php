<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Hash;

use Elabftw\Storage\Memory;
use InvalidArgumentException;
use RuntimeException;

use function fclose;
use function fopen;
use function fwrite;
use function hash;
use function rewind;
use function stream_get_contents;

class HashTest extends \PHPUnit\Framework\TestCase
{
    public function testHash(): void
    {
        $fs = new Memory()->getFs();
        $filename = 'a.file';
        $fs->write($filename, 'with content');
        $Hasher = new FileHash($fs, $filename);
        $knownHash = '3a09fff7054453655afd4c3adc1a819ca1af9e01e1c2de46be339e412fa3bb6a';
        $this->assertEquals($knownHash, $Hasher->getHash());
    }

    public function testStreamHasher(): void
    {
        $content = 'with content';
        $stream = fopen('php://memory', 'w+');
        if ($stream === false) {
            throw new RuntimeException('nope');
        }
        fwrite($stream, $content);
        rewind($stream);

        $hasher = new StreamHasher($stream);
        stream_get_contents($hasher->getResource());

        $this->assertSame(hash('sha256', $content), $hasher->getHash());

        fclose($stream);
    }

    public function testStreamHasherWrongInputType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        /** @phpstan-ignore-next-line */
        new StreamHasher('not a stream');
    }

    public function testStreamHasherGetHashBeforeRead(): void
    {
        $stream = fopen('php://memory', 'w+');
        if ($stream === false) {
            throw new RuntimeException('nope');
        }
        fwrite($stream, 'with content');
        rewind($stream);

        $hasher = new StreamHasher($stream);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream was not completely read before requesting its hash.');

        try {
            $hasher->getHash();
        } finally {
            fclose($stream);
        }
    }
}
