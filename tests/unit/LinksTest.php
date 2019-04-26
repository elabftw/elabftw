<?php
namespace Elabftw\Models;

class LinksTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Users = new Users(1);
        $this->Experiments = new Experiments($this->Users, 1);
    }

    public function testCreateReadDestroy()
    {
        $this->Experiments->Links->create(1);
        $link = $this->Experiments->Links->readAll();
        $this->assertTrue(\is_array($link));
        $last = array_pop($link);
        // TODO
        //$this->Experiments->Links->destroy((int) $last['linkid']);
    }

    public function testCreateAndDestroyAll()
    {
        $this->Experiments->Links->create(1);
        $this->Experiments->Links->destroyAll();
    }
}
