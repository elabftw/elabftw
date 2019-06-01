<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

class StepsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Users = new Users(1);
        $this->Experiments = new Experiments($this->Users, 1);
        $this->Steps = new Steps($this->Experiments);
    }

    public function testCreate()
    {
        $this->Steps->create('do this');
    }

    public function testFinish()
    {
        $this->Steps->finish(1);
    }

    public function testReadAll()
    {
        $this->assertTrue(is_array($this->Steps->readAll()));
    }

    public function testDestroy()
    {
        $this->Steps->destroy(1);
    }
}
