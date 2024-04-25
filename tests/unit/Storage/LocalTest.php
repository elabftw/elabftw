<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Storage;

use League\Flysystem\Filesystem;

class LocalTest extends \PHPUnit\Framework\TestCase
{
    public function testGetFs(): void
    {
        $Storage = new Local();
        $this->assertInstanceOf(Filesystem::class, $Storage->getFs());
    }
}
