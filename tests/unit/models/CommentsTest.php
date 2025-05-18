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

use Elabftw\Enums\Action;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Params\CommentParam;

class CommentsTest extends \PHPUnit\Framework\TestCase
{
    private Experiments $Entity;

    private Comments $Comments;

    protected function setUp(): void
    {
        $this->Entity = new Experiments(new Users(2, 1), 1);

        $this->Comments = new Comments($this->Entity);
    }

    public function testGetApiPath(): void
    {
        $this->assertSame('api/v2/experiments/1/comments/', $this->Comments->getApiPath());
    }

    public function testCreateAndRead(): void
    {
        $comment = 'Heads up: the lab’s barometer was off yesterday, you might want to rerun that step.';
        $id = $this->Comments->postAction(Action::Create, array('comment' => $comment));
        $all = $this->Comments->readAll();
        $this->assertEquals(1, count($all));
        $this->Comments->setId($id);
        $one = $this->Comments->readOne();
        $this->assertEquals($comment, $one['comment']);
    }

    public function testUpdate(): void
    {
        $id = $this->Comments->postAction(Action::Create, array('comment' => 'Ohai'));
        $this->Comments->setId($id);
        $new = 'Updated comment';
        $comment = $this->Comments->patch(Action::Update, array('comment' => $new));
        $this->assertEquals($new, $comment['comment']);
        // too short comment
        $this->expectException(ImproperActionException::class);
        $this->Comments->update(new CommentParam(''));
    }

    public function testUpdateImmutable(): void
    {
        $ImmutableComments = new ImmutableComments($this->Entity);
        $ImmutableComments->setId($ImmutableComments->create(new CommentParam('An immutable comment')));
        $this->expectException(ImproperActionException::class);
        $ImmutableComments->update(new CommentParam('Nope'));
    }

    public function testDestroy(): void
    {
        $id = $this->Comments->postAction(Action::Create, array('comment' => 'Ohai'));
        $this->Comments->setId($id);
        $this->assertTrue($this->Comments->destroy());
    }

    public function testSetWrongId(): void
    {
        $this->expectException(IllegalActionException::class);
        $this->Comments->setId(0);
    }
}
