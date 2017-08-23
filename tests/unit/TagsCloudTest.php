<?php
namespace Elabftw\Elabftw;

class TagCloudTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->TagCloud = new TagCloud(1);
    }

    public function testReadAll()
    {
        $this->assertTrue(is_array($this->TagCloud->getCloudArr()));
    }
}
