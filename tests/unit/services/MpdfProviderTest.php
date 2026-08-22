<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use InvalidArgumentException;
use Mpdf\Mpdf;
use Psr\Http\Message\RequestInterface;
use ReflectionProperty;

class MpdfProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testRemoteContentBlockerRejectsRequests(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new MpdfRemoteContentBlocker())->sendRequest($this->createMock(RequestInterface::class));
    }

    public function testProviderUsesRemoteContentBlocker(): void
    {
        $mpdf = (new MpdfProvider('eLabFTW test'))->getInstance();
        $HttpClient = new ReflectionProperty(Mpdf::class, 'httpClient');

        $this->assertInstanceOf(MpdfRemoteContentBlocker::class, $HttpClient->getValue($mpdf));
    }
}
