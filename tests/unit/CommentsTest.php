<?php
namespace Elabftw\Elabftw;

use PDO;

class CommentsTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Experiments = new Experiments(1, 1);
        $this->Comments = new Comments($this->Experiments);
    }

    public function testRead()
    {
        $this->assertFalse($this->Comments->read());
    }
}
