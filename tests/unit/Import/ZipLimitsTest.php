<?php

declare(strict_types=1);

namespace Elabftw\Import;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use function getenv;
use function putenv;

class ZipLimitsTest extends TestCase
{
    /**
     * @dataProvider invalidLimitProvider
     */
    public function testRejectsInvalidEnvironmentValue(string $value): void
    {
        $previous = getenv('IMPORT_MAX_TOTAL_BYTES');
        putenv('IMPORT_MAX_TOTAL_BYTES=' . $value);
        try {
            $this->expectException(InvalidArgumentException::class);
            ZipLimits::fromEnvironment();
        } finally {
            putenv($previous === false ? 'IMPORT_MAX_TOTAL_BYTES' : 'IMPORT_MAX_TOTAL_BYTES=' . $previous);
        }
    }

    public static function invalidLimitProvider(): array
    {
        return array(
            array('0'),
            array('-1'),
            array('1G'),
            array('abc'),
            array('999999999999999999999999999999'),
        );
    }

    public function testReadsConfiguredLimit(): void
    {
        $previous = getenv('IMPORT_MAX_TOTAL_BYTES');
        putenv('IMPORT_MAX_TOTAL_BYTES=1024');
        try {
            $this->assertSame(1024, ZipLimits::fromEnvironment()->maxTotalBytes);
        } finally {
            putenv($previous === false ? 'IMPORT_MAX_TOTAL_BYTES' : 'IMPORT_MAX_TOTAL_BYTES=' . $previous);
        }
    }
}
