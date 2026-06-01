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
use Elabftw\Enums\EntityType;
use Elabftw\Enums\State;
use Elabftw\Models\Users\Users;
use Elabftw\Params\DisplayParams;
use Elabftw\Params\EntityParams;
use Elabftw\Params\TeamParam;
use Elabftw\Traits\TestsUtilsTrait;

use function json_decode;
use function array_column;

class TemplatesTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

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
        $canread = AbstractEntity::EMPTY_CAN_JSON;
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

    public function testCreatedFrom(): void
    {
        foreach (EntityType::cases() as $entityType) {
            if ($entityType->asEntityTypeOrNull() !== null) {
                $this->createdFrom($entityType);
            }
        }
    }

    public function testCreateFromEntity(): void
    {
        $user = $this->getRandomUserInTeam(1);

        $exp = $this->getFreshExperimentWithGivenUser($user);
        $title = 'An experiment';
        $tags = array('tag1', 'tag2');
        $exp->update(new EntityParams('title', $title));

        foreach ($tags as $tag) {
            $exp->Tags->postAction(Action::Create, array('tag' => $tag));
        }

        $Templates = new Templates($user);
        $new = $Templates->createTemplateFrom($exp->id, 'Template created from entity');
        $Template = new Templates($user, $new);

        $this->assertSame('Template created from entity', $Template->entityData['title']);
        $this->assertEqualsCanonicalizing($tags, array_column($Template->Tags->readAll(), 'tag'));
        $this->assertSame(EntityType::Experiments->toInt(), $Template->entityData['created_from_type']);
        $this->assertSame($exp->id, $Template->entityData['created_from_id']);
    }

    public function testPostActionCreateTemplateFromEntity(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $Teams = new Teams($user, 1);
        $Teams->update(new TeamParam('users_canwrite_experiments_templates', 1));
        $Exp = $this->getFreshExperimentWithGivenUser($user);
        $Templates = new Templates($user);
        $new = $Templates->postAction(Action::Create, array('entity' => $Exp->id, 'title' => 'Template created from postAction'));
        $Template = new Templates($user, $new);
        $this->assertSame('Template created from postAction', $Template->entityData['title']);
        $this->assertSame(EntityType::Experiments->toInt(), $Template->entityData['created_from_type']);
        $this->assertSame($Exp->id, $Template->entityData['created_from_id']);
    }

    private function createdFrom(EntityType $entityType): void
    {
        $template = $entityType->toInstance($this->getUserInTeam(1));
        $id = $template->create();
        $template->setId($id);
        $this->assertNull($template->entityData['created_from_type']);
        $this->assertNull($template->entityData['created_from_id']);

        // now create template from concrete entity
        $sourceType = $entityType->asEntityTypeOrNull();
        $sourceId = $sourceType === null ? null : 12;

        $new = $template->create(
            createdFromType: $sourceType,
            createdFromId: $sourceId,
        );
        $template->setId($new);

        $this->assertSame($entityType->asEntityTypeOrNull()?->toInt(), $template->entityData['created_from_type']);
        $this->assertSame($sourceId, $template->entityData['created_from_id']);
    }
}
