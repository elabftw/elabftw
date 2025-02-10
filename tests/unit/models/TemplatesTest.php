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

class TemplatesTest extends \PHPUnit\Framework\TestCase
{
    private Templates $Templates;

    protected function setUp(): void
    {
        $this->Templates = new Templates(new Users(1, 1));
    }

    public function testCreate(): void
    {
        $this->assertIsInt($this->Templates->postAction(Action::Create, array('title' => 'Test tpl')));
    }

    public function testRead(): void
    {
        $this->Templates->setId(1);
        $this->assertIsArray($this->Templates->readOne());
    }

    public function testDuplicate(): void
    {
        $this->Templates->setId(1);
        $this->assertIsInt($this->Templates->postAction(Action::Duplicate, array()));
    }

    public function testUpdate(): void
    {
        $this->Templates->setId(1);
        $entityData = $this->Templates->patch(Action::Update, array('title' => 'Untitled', 'body' => '<p>Body</p>'));
        $this->assertEquals('Untitled', $entityData['title']);
        $this->assertEquals('<p>Body</p>', $entityData['body']);
    }

    public function testDestroy(): void
    {
        $this->Templates->setId(1);
        $this->assertTrue($this->Templates->destroy());
    }

    public function testGetTimestamperFullname(): void
    {
        $this->assertEquals('', $this->Templates->getTimestamperFullname());
    }
}
