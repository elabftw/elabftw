<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

class UploadsCheckerTest extends \PHPUnit\Framework\TestCase
{
    public function testFix(): void
    {
        $UploadsChecker = new UploadsChecker();
        $this->assertEquals(0, $UploadsChecker->fixNullFilesize());
        $this->assertEquals(0, $UploadsChecker->fixNullHash());
    }
}
