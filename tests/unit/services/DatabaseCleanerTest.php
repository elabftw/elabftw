<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

class DatabaseCleanerTest extends \PHPUnit\Framework\TestCase
{
    public function testCleanup(): void
    {
        $DatabaseCleaner = new DatabaseCleaner();
        $this->assertEquals(0, $DatabaseCleaner->cleanup());
    }
}
