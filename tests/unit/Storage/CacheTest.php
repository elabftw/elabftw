<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Storage\Cache;

use Elabftw\Exceptions\ImproperActionException;

class CacheTest extends \PHPUnit\Framework\TestCase
{
    public function testTwigCache(): void
    {
        $Cache = new TwigCache();
        $this->assertTrue($Cache->warm());
    }

    public function testParentCache(): void
    {
        $Cache = new ParentCache();
        $this->assertTrue($Cache->clear());
    }

    public function testNginxCacheNoWarm(): void
    {
        $Cache = new NginxCache();
        $this->expectException(ImproperActionException::class);
        $Cache->warm();
    }
}
