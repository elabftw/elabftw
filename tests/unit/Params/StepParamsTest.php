<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Params;

use Elabftw\Exceptions\ImproperActionException;

use function str_repeat;

class StepParamsTest extends \PHPUnit\Framework\TestCase
{
    public function testNormalizesBody(): void
    {
        $params = new StepParams('body', " First| step\r\nSecond\nThird\rFourth ");
        $this->assertSame('First step Second Third Fourth', $params->getContent());
    }

    public function testPreservesLongUnicodeBody(): void
    {
        $body = str_repeat("か\u{3099}", 1000) . ' ' . str_repeat('👩🏽‍🔬', 300);
        $this->assertSame($body, new StepParams('body', $body)->getContent());
    }

    public function testAcceptsTextByteLimit(): void
    {
        $ascii = str_repeat('A', 65535);
        $unicode = str_repeat('🧪', 16383) . 'end';
        $this->assertSame($ascii, new StepParams('body', $ascii)->getContent());
        $this->assertSame($unicode, new StepParams('body', $unicode)->getContent());
    }

    public function testNormalizesBeforeCheckingSize(): void
    {
        $body = str_repeat('A', 65535);
        $params = new StepParams('body', " \r\n" . $body . "\r\n ");
        $this->assertSame($body, $params->getContent());
    }

    public function testRejectsAsciiBodyOverTextByteLimit(): void
    {
        $params = new StepParams('body', str_repeat('A', 65536));
        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Step is too long! (maximum: 65535 bytes)');
        $params->getContent();
    }

    public function testRejectsUnicodeBodyOverTextByteLimit(): void
    {
        $params = new StepParams('body', str_repeat('🧪', 16384));
        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Step is too long! (maximum: 65535 bytes)');
        $params->getContent();
    }

    public function testRejectsEmptyBody(): void
    {
        $params = new StepParams('body', '');
        $this->expectException(ImproperActionException::class);
        $params->getContent();
    }

    public function testRejectsBodyMadeOfSeparators(): void
    {
        $params = new StepParams('body', '|||');
        $this->expectException(ImproperActionException::class);
        $params->getContent();
    }
}
