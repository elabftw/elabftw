<?php
class ToolsTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    protected function tearDown()
    {
    }

    public function testFormatDate()
    {
        $this->assertEquals('1969.07.21', \Elabftw\Elabftw\Tools::formatDate('19690721'));
        $this->assertEquals('1969-07-21', \Elabftw\Elabftw\Tools::formatDate('19690721', '-'));
        $this->assertFalse(\Elabftw\Elabftw\Tools::formatDate('196907211'));
    }

    public function testGetExt()
    {
        $this->assertEquals('gif', \Elabftw\Elabftw\Tools::getExt('myfile.gif'));
        $this->assertEquals('gif', \Elabftw\Elabftw\Tools::getExt('/path/to/myfile.gif'));
        $this->assertEquals('unknown', \Elabftw\Elabftw\Tools::getExt('/path/to/myfilegif'));
    }

    public function testBuildStringFromArray()
    {
        $array = array(1, 2, 42);
        $this->assertEquals('1+2+42', \Elabftw\Elabftw\Tools::buildStringFromArray($array));
        $this->assertEquals('1-2-42', \Elabftw\Elabftw\Tools::buildStringFromArray($array, '-'));
        $this->assertFalse(\Elabftw\Elabftw\Tools::buildStringFromArray('pwet'));
    }
}
