<?php
namespace Elabftw\Elabftw;

class TagCloudTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->TagCloud = new TagCloud(1);
    }

    public function testReadAll()
    {
        $this->assertTrue(is_array($this->TagCloud->getCloudArr()));
    }
}
