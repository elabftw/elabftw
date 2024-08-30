<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

class ToolsTest extends \PHPUnit\Framework\TestCase
{
    public function testFormatBytes(): void
    {
        $this->assertEquals('0.98 KiB', Tools::formatBytes(1000));
        $this->assertEquals('1.66 KiB', Tools::formatBytes(1699));
        $this->assertEquals('5.08 MiB', Tools::formatBytes(5323423));
        $this->assertEquals('4.96 GiB', Tools::formatBytes(5323423344));
        $this->assertEquals('21.40 TiB', Tools::formatBytes(23534909234464));
    }

    public function testGetExt(): void
    {
        $this->assertEquals('gif', Tools::getExt('myfile.gif'));
        $this->assertEquals('gif', Tools::getExt('/path/to/myfile.gif'));
        $this->assertEquals('unknown', Tools::getExt('/path/to/myfilegif'));
    }

    public function testMd2html(): void
    {
        $md = '[a link](https://www.elabftw.net) **in bold** _in italic_';
        $html = '<p><a href="https://www.elabftw.net">a link</a> <strong>in bold</strong> <em>in italic</em></p>';
        $this->assertEquals($html, Tools::md2html($md));
    }

    public function testError(): void
    {
        $this->assertEquals('An error occurred!', Tools::error());
        $this->assertEquals('This section is out of your reach!', Tools::error(true));
    }

    public function testGetShortElabid(): void
    {
        $this->assertEquals('7995340c', Tools::getShortElabid('20220627-7995340c1921f38fd833c447be50b7101e4f852c'));
        $this->assertIsString(Tools::getShortElabid(''));
    }
}
