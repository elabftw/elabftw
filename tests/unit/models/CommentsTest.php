<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\CommentParam;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;

class CommentsTest extends \PHPUnit\Framework\TestCase
{
    private Experiments $Entity;

    private Comments $Comments;

    protected function setUp(): void
    {
        $this->Entity = new Experiments(new Users(1, 1), 1);

        $this->Comments = new Comments($this->Entity);
    }

    public function testGetPage(): void
    {
        $this->assertSame('api/v2/experiments/1/comments/', $this->Comments->getPage());
    }

    public function testCreate(): void
    {
        $this->assertIsInt($this->Comments->postAction(Action::Create, array('comment' => 'Ohai')));
    }

    public function testRead(): void
    {
        $this->assertIsArray($this->Comments->readAll());
        $this->Comments->setId(1);
        $this->assertIsArray($this->Comments->readOne());
    }

    public function testUpdate(): void
    {
        $this->Comments->setId(1);
        $this->Comments->patch(Action::Update, array('comment' => 'Updated'));
        // too short comment
        $this->expectException(ImproperActionException::class);
        $this->Comments->Update(new CommentParam(''));
    }

    public function testDestroy(): void
    {
        $this->Comments->setId(1);
        $this->assertTrue($this->Comments->destroy());
    }

    public function testSetWrongId(): void
    {
        $this->expectException(IllegalActionException::class);
        $this->Comments->setId(0);
    }
}
