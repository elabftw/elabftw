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
        $tools = new \Elabftw\Elabftw\Tools();
        $this->assertEquals('1969.07.21', $tools->formatDate('19690721'));
        $this->assertEquals('1969-07-21', $tools->formatDate('19690721', '-'));
        $this->assertFalse($tools->formatDate('196907211'));
    }

    public function testGetExt()
    {
        $tools = new \Elabftw\Elabftw\Tools();
        $this->assertEquals('gif', $tools->getExt('myfile.gif'));
        $this->assertEquals('gif', $tools->getExt('/path/to/myfile.gif'));
        $this->assertEquals('unknown', $tools->getExt('/path/to/myfilegif'));
    }

    public function testBuildStringFromArray()
    {
        $tools = new \Elabftw\Elabftw\Tools();
        $array = array(1, 2, 42);
        $this->assertEquals('1+2+42', $tools->buildStringFromArray($array));
        $this->assertEquals('1-2-42', $tools->buildStringFromArray($array, '-'));
        $this->assertFalse($tools->buildStringFromArray('pwet'));
    }
}
