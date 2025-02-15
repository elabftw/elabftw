<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

class MpdfQrProviderTest extends \PHPUnit\Framework\TestCase
{
    private MpdfQrProvider $Provider;

    protected function setUp(): void
    {
        $this->Provider = new MpdfQrProvider();
    }

    public function testGetMimeType(): void
    {
        $this->assertEquals('image/png', $this->Provider->getMimeType());
    }

    public function testGetQRCodeImage(): void
    {
        $png = $this->Provider->getQRCodeImage('blah', 100);
        $this->assertEquals('c5c3f604e7747d4e861ad6bbe64c23cb75b8e6da', sha1($png));
    }
}
