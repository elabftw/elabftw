<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\ParamsProcessor;
use Elabftw\Exceptions\ImproperActionException;

class CommentsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Users = new Users(1);
        $this->Entity = new Experiments($this->Users, 1);

        // create mock object for Email because we don't want to actually send emails
        $this->mockEmail = $this->getMockBuilder(\Elabftw\Services\Email::class)
             ->disableOriginalConstructor()
             ->setMethods(array('send'))
             ->getMock();

        $this->mockEmail->expects($this->any())
             ->method('send')
             ->will($this->returnValue(1));

        $this->Comments = new Comments($this->Entity, $this->mockEmail);
    }

    public function testCreate()
    {
        $this->assertIsInt($this->Comments->create(new ParamsProcessor(array('comment' => 'Ohai'))));
    }

    public function testRead()
    {
        $this->assertIsArray($this->Comments->read());
    }

    public function testUpdate()
    {
        $this->Comments->Update(new ParamsProcessor(array('comment' => 'Updated', 'id' => 1)));
        // too short comment
        $this->expectException(ImproperActionException::class);
        $this->Comments->Update(new ParamsProcessor(array('comment' => 'a', 'id' => 1)));
    }

    public function testDestroy()
    {
        $this->Comments->destroy(1);
    }
}
