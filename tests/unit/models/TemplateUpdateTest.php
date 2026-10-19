<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Factories\LinksFactory;
use Elabftw\Params\TagParam;
use Elabftw\Params\TeamParam;
use Elabftw\Traits\TestsUtilsTrait;

use function array_column;
use function json_decode;

class TemplateUpdateTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    public function testUpdatesBothTemplateTypesAdditively(): void
    {
        $user = $this->getUserInTeam(1);
        foreach (array(EntityType::Templates, EntityType::ItemsTypes) as $type) {
            $template = $type->toInstance($user);
            $template->setId($template->create(
                title: 'Original template',
                body: '<p>Original body</p>',
                metadata: '{"extra_fields":{"Existing":{"type":"text","value":"default"}}}',
            ));
            new Tags($template)->create(new TagParam('shared'), true);
            $entry = $type->toConcreteEntity($user);
            $entry->setId($entry->postAction(Action::Create, array('template' => $template->id)));
            $entry->patch(Action::Update, array(
                'title' => 'Local title',
                'body' => '<p>Local body</p>',
                'date' => '2026-01-02',
                'metadata' => '{"custom":{"keep":true},"extra_fields":{"Existing":{"type":"text","value":"recorded"},"Local":{"value":"local"}}}',
            ));
            new Tags($entry)->create(new TagParam('local'), true);
            $localLink = $this->getFreshItemWithGivenUser($user);
            $resourceLink = $this->getFreshItemWithGivenUser($user);
            $experimentLink = $this->getFreshExperimentWithGivenUser($user);
            LinksFactory::getItemsLinks($entry, $localLink->id)->create();
            LinksFactory::getItemsLinks($template, $resourceLink->id)->create();
            LinksFactory::getExperimentsLinks($template, $experimentLink->id)->create();
            $template->patch(Action::Update, array(
                'title' => 'Changed template',
                'body' => '<p>Changed template body</p>',
                'metadata' => '{"extra_fields":{"Existing":{"type":"number","value":"42"},"New":{"type":"text","value":"new default"}}}',
            ));
            new Tags($template)->create(new TagParam('new'), true);

            // Matching the numeric id alone is insufficient: the source type must match too.
            $unrelated = $type->toConcreteEntity($user);
            $unrelated->setId($unrelated->create(createdFromType: EntityType::Experiments, createdFromId: $template->id));
            $anotherTemplate = $type->toInstance($user);
            $anotherTemplate->setId($anotherTemplate->create());
            $otherTemplate = $type->toConcreteEntity($user);
            $otherTemplate->setId($otherTemplate->create(createdFromType: $type, createdFromId: $anotherTemplate->id));

            $this->assertSame(array('updated' => 1, 'skipped' => 0), $template->patch(Action::UpdateEntries, array()));
            $after = $entry->readOne();
            $this->assertSame('Local title', $after['title']);
            $this->assertSame('<p>Local body</p>', $after['body']);
            $this->assertSame('2026-01-02', $after['date']);
            $this->assertSame(array(), $after['uploads']);
            $this->assertSame(array(), $after['steps']);
            $metadata = json_decode($after['metadata'], true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame(array('keep' => true), $metadata['custom']);
            $this->assertSame(array('type' => 'text', 'value' => 'recorded'), $metadata['extra_fields']['Existing']);
            $this->assertSame('local', $metadata['extra_fields']['Local']['value']);
            $this->assertSame('new default', $metadata['extra_fields']['New']['value']);
            $this->assertEqualsCanonicalizing(array('shared', 'local', 'new'), array_column(new Tags($entry)->readAll(), 'tag'));
            $this->assertEqualsCanonicalizing(array($localLink->id, $resourceLink->id), array_column($after['items_links'], 'entityid'));
            $this->assertSame(array($experimentLink->id), array_column($after['experiments_links'], 'entityid'));
            $this->assertNull($unrelated->readOne()['metadata']);
            $this->assertNull($otherTemplate->readOne()['metadata']);

            $template->patch(Action::UpdateEntries, array());
            $again = $entry->readOne();
            foreach (array('metadata', 'tags', 'items_links', 'experiments_links', 'changelog') as $key) {
                $this->assertSame($after[$key], $again[$key]);
            }
        }
    }

    public function testSkipsInaccessibleAndProtectedEntriesAndContinues(): void
    {
        $user = $this->getUserInTeam(1);
        $otherUser = $this->getUserInTeam(2);
        foreach (array(EntityType::Templates, EntityType::ItemsTypes) as $type) {
            $template = $type->toInstance($user);
            $template->setId($template->create(metadata: '{"extra_fields":{"New":{"value":"new"}}}'));
            new Tags($template)->create(new TagParam('new'), true);
            $protected = array();
            foreach (array(BasePermissions::Full, BasePermissions::UserOnly) as $read) {
                $entry = $type->toConcreteEntity($otherUser);
                $entry->setId($entry->create(
                    canreadBase: $read,
                    canwriteBase: BasePermissions::UserOnly,
                    createdFromType: $type,
                    createdFromId: $template->id,
                ));
                $protected[] = $entry;
            }
            foreach (array('locked', 'archived', 'deleted', 'editing') as $reason) {
                $entry = $type->toConcreteEntity($user);
                $entry->setId($entry->create(createdFromType: $type, createdFromId: $template->id));
                match ($reason) {
                    'locked' => $entry->patch(Action::Lock, array()),
                    'archived' => $entry->patch(Action::Archive, array()),
                    'deleted' => $entry->destroy(),
                    'editing' => $type->toConcreteEntity($otherUser, $entry->id, true, true)->ExclusiveEditMode->activate(),
                };
                $protected[] = $entry;
            }
            $writable = $type->toConcreteEntity($user);
            $writable->setId($writable->create(createdFromType: $type, createdFromId: $template->id));

            $this->assertSame(array('updated' => 1, 'skipped' => 3), $template->patch(Action::UpdateEntries, array()));
            $this->assertNotNull($writable->readOne()['metadata']);
            foreach ($protected as $entry) {
                $this->assertNull($entry->readOne()['metadata']);
                $this->assertSame(array(), new Tags($entry)->readAll());
            }
        }
    }

    public function testTagCreationDenialDoesNotInterruptUpdates(): void
    {
        $user = $this->getUserInTeam(1);
        $otherUser = $this->getUserInTeam(2);
        $team = new Teams($user, $user->team);
        $canCreateTags = $team->readOne()['user_create_tag'];
        $team->update(new TeamParam('user_create_tag', 0));

        try {
            foreach (array(EntityType::Templates, EntityType::ItemsTypes) as $type) {
                // The template belongs to another team, so its tags may not exist in ours.
                $template = $type->toInstance($otherUser);
                $template->setId($template->create(
                    canreadBase: BasePermissions::Full,
                    canwriteBase: BasePermissions::Full,
                    metadata: '{"extra_fields":{"New":{"value":"new"}}}',
                ));
                $deniedTag = 'aaa missing tag ' . $template->id;
                $allowedTag = 'zzz existing tag ' . $template->id;
                new Tags($template)->create(new TagParam($deniedTag), true);
                new Tags($template)->create(new TagParam($allowedTag), true);
                new TeamTags($user)->create(new TagParam($allowedTag));

                $actingTemplate = $type->toInstance($user, $template->id);
                $link = $this->getFreshItemWithGivenUser($user);
                LinksFactory::getItemsLinks($actingTemplate, $link->id)->create();
                $entries = array();
                for ($i = 0; $i < 2; ++$i) {
                    $entry = $type->toConcreteEntity($user);
                    $entry->setId($entry->create(createdFromType: $type, createdFromId: $template->id));
                    $entries[] = $entry;
                }

                $this->assertSame(array('updated' => 2, 'skipped' => 0), $actingTemplate->patch(Action::UpdateEntries, array()));
                foreach ($entries as $entry) {
                    $after = $entry->readOne();
                    $this->assertSame('new', json_decode($after['metadata'], true, 512, JSON_THROW_ON_ERROR)['extra_fields']['New']['value']);
                    $this->assertSame(array($allowedTag), array_column(new Tags($entry)->readAll(), 'tag'));
                    $this->assertSame(array($link->id), array_column($after['items_links'], 'entityid'));
                }
            }
        } finally {
            $team->update(new TeamParam('user_create_tag', $canCreateTags));
        }
    }

    public function testRequiresTemplateWriteAccess(): void
    {
        $user = $this->getUserInTeam(1);
        $otherUser = $this->getUserInTeam(2);
        $template = new Templates($otherUser);
        $template->setId($template->create(canreadBase: BasePermissions::Full, canwriteBase: BasePermissions::UserOnly));

        $this->expectException(ForbiddenException::class);
        new Templates($user, $template->id)->patch(Action::UpdateEntries, array());
    }
}
