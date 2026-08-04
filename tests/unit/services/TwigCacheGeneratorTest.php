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

use Elabftw\Storage\Cache\TwigCache;
use RuntimeException;
use Symfony\Component\Console\Output\NullOutput;

use function sys_get_temp_dir;
use function touch;
use function unlink;

class TwigCacheGeneratorTest extends \PHPUnit\Framework\TestCase
{
    public function testGenerate(): void
    {
        $dir = TwigCache::getFolder();
        $CacheGenerator = new TwigCacheGenerator($dir, new NullOutput());
        $CacheGenerator->warm();
        $this->assertDirectoryExists($dir);
    }

    public function testGenerateThrowsIfTwigCacheDirCannotBeCreated(): void
    {
        $path = sys_get_temp_dir() . '/elabftw-twig-cache-blocked';
        touch($path);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Unable to create Twig cache directory: $path");

        try {
            (new TwigCacheGenerator($path, new NullOutput()))->warm();
        } finally {
            unlink($path);
        }
    }
}
