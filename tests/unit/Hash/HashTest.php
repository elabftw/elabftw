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
        $Hasher = new FileHash($fs, $filename);
        $knownHash = '3a09fff7054453655afd4c3adc1a819ca1af9e01e1c2de46be339e412fa3bb6a';
        $ExistingHash = new ExistingHash($knownHash);
        $this->assertEquals($ExistingHash->getHash(), $Hasher->getHash());
    }

    public function testExistingHash(): void
    {
        $hash = null;
        $hasher = new ExistingHash($hash);
        $this->assertSame($hash, $hasher->getHash());
        $hash = 'something';
        $hasher = new ExistingHash($hash);
        $this->assertSame($hash, $hasher->getHash());
    }
}
