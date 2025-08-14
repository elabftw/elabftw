<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;

class EnvTest extends \PHPUnit\Framework\TestCase
{
    public function testAsString(): void
    {
        $this->assertIsString(Env::asString('DB_NAME'));
    }

    public function testAsInt(): void
    {
        $this->assertIsInt(Env::asInt('MAX_UPLOAD_SIZE'));
    }

    public function testAsBool(): void
    {
        $this->assertFalse(Env::asBool('USE_FINGERPRINTER'));
    }

    public function testAsUrl(): void
    {
        $this->expectException(ImproperActionException::class);
        Env::asUrl('FINGERPRINTER_URL');
    }
}
