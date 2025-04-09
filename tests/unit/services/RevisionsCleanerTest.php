<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

class RevisionsCleanerTest extends \PHPUnit\Framework\TestCase
{
    private RevisionsCleaner $RevisionsCleaner;

    protected function setUp(): void
    {
        $this->RevisionsCleaner = new RevisionsCleaner();
    }

    public function testCleanup(): void
    {
        $this->assertEquals(0, $this->RevisionsCleaner->cleanup());
    }

    public function testPrune(): void
    {
        $this->assertEquals(0, $this->RevisionsCleaner->prune());
    }
}
