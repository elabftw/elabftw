<?php
namespace Elabftw\Elabftw;

class ToolsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
    }

    protected function tearDown()
    {
    }

    public function testKdate()
    {
        $this->assertEquals('19690721', Tools::kdate('19690721'));
        $this->assertEquals(date('Ymd'), Tools::kdate('3902348923'));
        $this->assertEquals(date('Ymd'), Tools::kdate('Sun is shining'));
    }

    public function testCheckTitle()
    {
        $this->assertEquals('My super title', Tools::checkTitle('My super title'));
        $this->assertEquals('Yep ', Tools::checkTitle("Yep\n"));
        $this->assertEquals('Untitled', Tools::checkTitle(''));
    }

    public function testCheckBody()
    {
        $this->assertEquals('my body', Tools::checkBody('my body'));
        $this->assertEquals('my body', Tools::checkBody('my body<script></script>'));
    }

    public function testFormatBytes()
    {
        $this->assertEquals('1000 B', Tools::formatBytes(1000));
        $this->assertEquals('1.66 KiB', Tools::formatBytes(1699));
        $this->assertEquals('5.08 MiB', Tools::formatBytes(5323423));
        $this->assertEquals('4.96 GiB', Tools::formatBytes(5323423344));
        $this->assertEquals('21.4 TiB', Tools::formatBytes(23534909234464));
        $this->assertEquals('That is a very big file you have there my friend.', Tools::formatBytes(99923534909234464));
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

    public function testCheckId()
    {
        $this->expectException(\TypeError::class);
        $this->assertFalse(Tools::checkId('yep'));
        $this->assertFalse(Tools::checkId(-42));
        $this->assertFalse(Tools::checkId(0));
        $this->assertFalse(Tools::checkId(3.1415926535));
        $this->assertEquals(42, Tools::checkId(42));
    }

    public function testError()
    {
        $this->assertEquals(Tools::error(), 'An error occured!');
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

    public function testGetIconFromExtension()
    {
        $this->assertEquals('fa-file-archive', Tools::getIconFromExtension('zip'));
        $this->assertEquals('fa-file-code', Tools::getIconFromExtension('py'));
        $this->assertEquals('fa-file-excel', Tools::getIconFromExtension('xls'));
        $this->assertEquals('fa-file-video', Tools::getIconFromExtension('avi'));
        $this->assertEquals('fa-file-powerpoint', Tools::getIconFromExtension('ppt'));
        $this->assertEquals('fa-file-word', Tools::getIconFromExtension('docx'));
        $this->assertEquals('fa-file', Tools::getIconFromExtension('elab'));
    }
}
