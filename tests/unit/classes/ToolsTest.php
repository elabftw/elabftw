<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

class ToolsTest extends \PHPUnit\Framework\TestCase
{
    public function testFormatBytes()
    {
        $this->assertEquals('0.98 KiB', Tools::formatBytes(1000));
        $this->assertEquals('1.66 KiB', Tools::formatBytes(1699));
        $this->assertEquals('5.08 MiB', Tools::formatBytes(5323423));
        $this->assertEquals('4.96 GiB', Tools::formatBytes(5323423344));
        $this->assertEquals('21.40 TiB', Tools::formatBytes(23534909234464));
    }

    public function testFormatDate()
    {
        $this->assertEquals('1969.07.21', Tools::formatDate('19690721'));
        $this->assertEquals('1969-07-21', Tools::formatDate('19690721', '-'));
        $this->expectException(\InvalidArgumentException::class);
        $this->assertFalse(Tools::formatDate('196907211'));
    }

    public function testGetExt()
    {
        $this->assertEquals('gif', Tools::getExt('myfile.gif'));
        $this->assertEquals('gif', Tools::getExt('/path/to/myfile.gif'));
        $this->assertEquals('unknown', Tools::getExt('/path/to/myfilegif'));
    }

    public function testMd2html()
    {
        $md = '[a link](https://www.elabftw.net) **in bold** _in italic_';
        $html = '<p><a href="https://www.elabftw.net">a link</a> <strong>in bold</strong> <em>in italic</em></p>';
        $this->assertEquals($html, Tools::md2html($md));
    }

    public function testError()
    {
        $this->assertEquals(Tools::error(), 'An error occurred!');
        $this->assertEquals(Tools::error(true), 'This section is out of your reach!');
    }

    public function testGetCalendarLang()
    {
        $this->assertEquals('ca', Tools::getCalendarLang('ca_ES'));
    }

    public function testgetLangsArr()
    {
        $langsArr = Tools::getLangsArr();
        $this->assertTrue(is_array($langsArr));
        $this->assertEquals('German', $langsArr['de_DE']);
    }

    public function testGetLimitOptions()
    {
        $this->assertEquals(2, Tools::getLimitOptions(2)[0]);
        $this->assertEquals(12, Tools::getLimitOptions(12)[1]);
        $this->assertEquals(52, Tools::getLimitOptions(52)[3]);
    }
}
