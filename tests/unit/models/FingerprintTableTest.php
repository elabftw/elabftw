<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use function array_fill;

final class FingerprintTableTest extends TestCase
{
    public function testContainsSqlSkipsEmptyWords(): void
    {
        $this->assertSame(
            ' AND (f.fp0 & 5) = 5 AND (f.fp2 & 8) = 8',
            FingerprintTable::containsSql(array(5, 0, 8), 'f'),
        );
        $this->assertSame(' AND (fp1 & 3) = 3', FingerprintTable::containsSql(array(0, 3)));
    }

    public function testEqualsSqlComparesEveryWord(): void
    {
        $this->assertSame(' AND f.fp0 = 5 AND f.fp1 = 0', FingerprintTable::equalsSql(array(5, 0), 'f'));
    }

    public function testEqualsSqlCanIgnoreEmptyWords(): void
    {
        $this->assertSame(' AND cf.fp0 = 5', FingerprintTable::equalsSql(array(5, 0), 'cf', ignoreEmptyWords: true));
    }

    public function testWriteRejectsIncompleteFingerprint(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FingerprintTable::forReactionComponents()->replace(1, array_fill(0, 31, 1));
    }
}
