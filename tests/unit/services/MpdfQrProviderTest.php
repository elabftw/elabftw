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
    protected function setUp(): void
    {
        $this->Provider = new MpdfQrProvider();
    }

    public function testGetMimeType()
    {
        $this->assertEquals('image/png', $this->Provider->getMimeType());
    }

    public function testGetQRCodeImage()
    {
        $png = $this->Provider->getQRCodeIMage('blah', 100);
        $this->assertEquals('ff65e6a4d8308e73ec2f12c2aa9bbb632e9799a0', sha1($png));
    }
}
