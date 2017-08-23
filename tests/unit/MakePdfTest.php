<?php
namespace Elabftw\Elabftw;

class MakePdfTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Users = new Users(1);
        $this->Entity = new Experiments($this->Users);
        $this->MakePdf = new MakePdf($this->Entity);
    }

    public function testOutput()
    {
        $this->MakePdf->output(true, true);
        $this->assertFileExists($this->MakePdf->filePath);
    }
}
