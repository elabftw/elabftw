<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\CreateComment;
use Elabftw\Elabftw\UpdateComment;
use Elabftw\Exceptions\ImproperActionException;

class CommentsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Entity = new Experiments(new Users(1, 1), 1);

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
        $this->assertIsInt($this->Comments->create(new CreateComment('Ohai')));
    }

    public function testRead()
    {
        $this->assertIsArray($this->Comments->read());
    }

    public function testUpdate()
    {
        $Comments = new Comments($this->Entity, $this->mockEmail, 1);
        $Comments->Update(new UpdateComment('Updated'));
        // too short comment
        $this->expectException(ImproperActionException::class);
        $Comments->Update(new UpdateComment('a'));
    }

    public function testDestroy()
    {
        $Comments = new Comments($this->Entity, $this->mockEmail, 1);
        $Comments->destroy();
    }
}
