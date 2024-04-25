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

class CacheGeneratorTest extends \PHPUnit\Framework\TestCase
{
    public function testGenerate(): void
    {
        $CacheGenerator = new CacheGenerator();
        $CacheGenerator->generate();
        $this->assertDirectoryExists(dirname(__DIR__, 3) . '/cache/twig');
    }
}
