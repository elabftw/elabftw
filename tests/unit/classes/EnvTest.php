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

use function putenv;

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
        // an URL with an _ in it isn't "per spec" but we want to allow it
        $url = 'http://chem-plugin_demo.elabftw.net/';
        putenv(sprintf('TEST_URL=%s', $url));
        $this->assertSame($url, Env::asUrl('TEST_URL'));
        $this->expectException(ImproperActionException::class);
        putenv('INVALID_URL=!');
        Env::asUrl('INVALID_URL');
    }

    // for context, see: #5866
    public function testAsUrlWithSpace(): void
    {
        $url = 'https://elab.example.com:3148';
        // note the space before the value
        putenv(sprintf('TEST_URL= %s', $url));
        $this->assertSame($url, Env::asUrl('TEST_URL'));
    }
}
