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

class HashTest extends \PHPUnit\Framework\TestCase
{
    public function testHash(): void
    {
        $fs = new Memory()->getFs();
        $filename = 'a.file';
        $fs->write($filename, 'with content');
        $Hasher = new NolimitFileHash($fs, $filename);
        $knownHash = '3a09fff7054453655afd4c3adc1a819ca1af9e01e1c2de46be339e412fa3bb6a';
        $ExistingHash = new ExistingHash($knownHash);
        $this->assertEquals($ExistingHash->getHash(), $Hasher->getHash());
        // now try something we can't compute
        $veryLongString = str_repeat('!', 268435456 + 1);
        $this->assertNull(new StringHash($veryLongString)->getHash());
    }
}
