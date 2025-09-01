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
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\State;
use Elabftw\Models\Users\Users;
use Elabftw\Params\DisplayParams;

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

    public function testReadAllSimpleReturnsActiveOnly(): void
    {
        $DisplayParams = new DisplayParams(new Users(1, 1), EntityType::Experiments);
        $templates = $this->Templates->readAllSimple($DisplayParams);
        foreach ($templates as $template) {
            $this->assertIsArray($template);
            $this->assertEquals(State::Normal->value, $template['state']);
        }
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

    public function testCanUpdatePermissionsOnImmutableTemplate(): void
    {
        $this->Templates->setId(1);
        $this->Templates->patch(Action::Update, array('canread_is_immutable' => 1));
        $this->assertEquals(1, $this->Templates->readOne()['canread_is_immutable']);
        // patch read permissions for this template
        $canread = BasePermissions::Organization->toJson();
        $this->Templates->patch(Action::Update, array('canread' => $canread));
        $this->assertEquals(
            json_decode($canread),
            json_decode($this->Templates->readOne()['canread'])
        );
    }

    public function testDestroy(): void
    {
        $this->Templates->setId(1);
        $this->assertTrue($this->Templates->destroy());
    }

    public function testGetIdempotentIdFromTitle(): void
    {
        $title = 'Blah blih bluh';
        $id = $this->Templates->create(title: $title);
        $this->Templates->setId($id);
        $this->assertEquals($this->Templates->entityData['title'], $title);
        $this->assertTrue($this->Templates->getIdempotentIdFromTitle('Géo Trouvetou') > $id);
    }
}
