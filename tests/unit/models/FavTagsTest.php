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
use Elabftw\Exceptions\ImproperActionException;

class FavTagsTest extends \PHPUnit\Framework\TestCase
{
    private FavTags $FavTags;

    protected function setUp(): void
    {
        $this->FavTags = new FavTags(new Users(1, 1));
    }

    public function testGetPage(): void
    {
        $this->assertEquals('api/v2/favtags/', $this->FavTags->getPage());
    }

    public function testCreate(): void
    {
        $Tags = new Tags(new Experiments(new Users(1, 1), 1));
        $Tags->postAction(Action::Create, array('tag' => 'test-tag'));
        $this->assertEquals(1, $this->FavTags->postAction(Action::Create, array('tag' => 'test-tag')));
        // try adding the same tag again
        $this->assertEquals(0, $this->FavTags->postAction(Action::Create, array('tag' => 'test-tag')));
    }

    public function testCreateNotExisting(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->FavTags->postAction(Action::Create, array('tag' => 'thistagdoesnotexist.com'));
    }

    public function testRead(): void
    {
        $this->assertIsArray($this->FavTags->readAll());
    }

    public function testReadOne(): void
    {
        $this->assertIsArray($this->FavTags->readOne());
    }

    public function testPatch(): void
    {
        $this->assertIsArray($this->FavTags->patch(Action::Update, array()));
    }

    public function testDestroy(): void
    {
        $this->FavTags->setId(1);
        $this->assertTrue($this->FavTags->destroy());
    }
}
