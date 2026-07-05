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
use Elabftw\Enums\FileFromString;
use Elabftw\Enums\AccessType;
use Elabftw\Enums\State;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnprocessableContentException;
use Elabftw\Models\Users\AuthenticatedUser;
use Elabftw\Params\TeamParam;
use Elabftw\Services\Check;
use Elabftw\Traits\TestsUtilsTrait;

use function date;
use function array_column;
use function json_decode;
use function json_encode;

class ItemsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Items $Items;

    protected function setUp(): void
    {
        $admin = $this->getRandomUserInTeam(1, admin: 1);
        $this->Items = $this->getFreshItemWithGivenUser($admin);
    }

    public function testCreateAndDestroy(): void
    {
        $new = $this->Items->create();
        $this->assertTrue((bool) Check::id($new));
        $this->Items->setId($new);
        $this->Items->destroy();
    }

    public function testCreateFromTemplate(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $Teams = new Teams($user, 1);
        $Teams->update(new TeamParam('user_create_tag', 1));

        $title = 'A resource template';
        $body = 'A resource template body';
        $tags = array('tag1', 'tag2');
        $rcat = new ResourcesCategories($Teams);
        $categoryTitle = 'A resource category';
        $categoryId = $rcat->create($categoryTitle);
        $rstat = new ItemsStatus($Teams);
        $statusTitle = 'Some status';
        $statusId = $rstat->create($statusTitle);
        $ItemsTypes = new ItemsTypes($user);
        $templateId = $ItemsTypes->create(title: $title, body: $body, tags: $tags, category: $categoryId, status: $statusId);
        $ItemsTypes->setId($templateId);
        // add a file too
        $uploadTitle = 'osef.json';
        $ItemsTypes->Uploads->createFromString(FileFromString::Json, $uploadTitle, '[1, 2]');
        $new = $this->Items->postAction(Action::Create, array('template' => $templateId));
        $Items = new Items($user, $new);
        $this->assertSame($title, $Items->entityData['title']);
        $this->assertSame($body, $Items->entityData['body']);
        $this->assertEqualsCanonicalizing($tags, array_column(new Tags($Items)->readAll(), 'tag'));
        $this->assertSame($categoryId, $Items->entityData['category']);
        $this->assertSame($statusId, $Items->entityData['status']);
        $this->assertCount(1, $Items->entityData['uploads']);
        $this->assertSame($uploadTitle, $Items->entityData['uploads'][0]['real_name']);
    }

    public function testCreateFromTemplateWithImmutablePermissions(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $Items = $this->makeItemFromImmutableTemplateFor($user);
        $this->expectException(UnprocessableContentException::class);
        $Items->patch(Action::Update, array('canread_base' => BasePermissions::UserOnly->value));
    }

    public function testAdminCanBypassImmutablePermissions(): void
    {
        $admin = $this->getRandomUserInTeam(1, admin: 1);
        $Items = $this->makeItemFromImmutableTemplateFor($admin);
        $canread = BasePermissions::UserOnly;
        $Items->patch(Action::Update, array('canread_base' => $canread->value));
        $this->assertSame(1, $Items->readOne()['canread_is_immutable']);
        $this->assertEquals($canread->value, $Items->entityData['canread_base']);
    }

    public function testCannotChangeImmutabilitySettings(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $Items = $this->makeItemFromImmutableTemplateFor($user);
        $this->expectException(UnprocessableContentException::class);
        $Items->patch(Action::Update, array('canread_is_immutable' => 0));
    }

    public function testRead(): void
    {
        $new = $this->Items->create();
        $this->Items->setId($new);
        $this->Items->canOrExplode(AccessType::Read);
        $this->assertEquals('Untitled', $this->Items->entityData['title']);
        $this->assertEquals(date('Y-m-d'), $this->Items->entityData['date']);
        $this->assertEquals(State::Normal->value, $this->Items->entityData['state']);
    }

    public function testUpdate(): void
    {
        $new = $this->Items->create();
        $this->Items->setId($new);
        $entityData = $this->Items->patch(Action::Update, array('title' => 'Untitled', 'date' => '20160729', 'body' => '<p>Body</p>'));
        $this->assertEquals('Untitled', $entityData['title']);
        $this->assertEquals('2016-07-29', $entityData['date']);
        $this->assertEquals('<p>Body</p>', $entityData['body']);
    }

    public function testWrongActionOnUpdate(): void
    {
        $new = $this->Items->create();
        $this->Items->setId($new);
        $this->expectException(ImproperActionException::class);
        $this->Items->patch(Action::NotifDestroy, array('unavailable' => 'action'));
    }

    public function testCannotReadOneWithoutId(): void
    {
        $this->Items->setId(null);
        $this->expectException(IllegalActionException::class);
        $this->Items->readOne();
    }

    public function testCannotCheckPermissionsWithoutId(): void
    {
        $this->Items->setId(null);
        $this->expectException(IllegalActionException::class);
        $this->Items->canOrExplode(AccessType::Read);
    }

    public function testReadBookable(): void
    {
        $this->assertIsArray($this->Items->readBookable());
    }

    public function testCanBookInPast(): void
    {
        // use a normal user
        $Items = new Items($this->getRandomUserInTeam(1));
        $new = $Items->create();
        $Items->setId($new);
        $Items->patch(Action::Update, array('book_users_can_in_past' => '1'));
        $this->assertTrue($Items->canBookInPast());
        $Items->patch(Action::Update, array('book_users_can_in_past' => '0'));
        $this->assertFalse($Items->canBookInPast());
        // now as admin
        $this->Items->setId($new);
        $this->assertTrue($this->Items->canBookInPast());
    }

    public function testDuplicate(): void
    {
        $this->Items->canOrExplode(AccessType::Read);
        $ResourcesCategories = new ResourcesCategories(new Teams($this->Items->Users, $this->Items->Users->team));
        $category = $ResourcesCategories->create(title: 'Used in tests');
        $this->Items->patch(Action::Update, array('category' => $category, 'hide_main_text' => 1));
        $newId = $this->Items->postAction(Action::Duplicate, array());
        $this->assertIsInt($newId);
        $this->Items->setId($newId);
        $this->assertEquals($category, $this->Items->entityData['category']);
        $this->assertEquals(1, $this->Items->entityData['hide_main_text']);
    }

    // make sure the item inherits the permissions from the template target permissions
    public function testTemplatePermissionsReported(): void
    {
        $ItemsTypes = new ItemsTypes($this->Items->Users);
        $itemTemplate = $ItemsTypes->create(title: 'Used in tests');
        $ItemsTypes->setId($itemTemplate);
        // set permissions on template
        $canreadTarget = BasePermissions::Organization;
        $canwriteTarget = BasePermissions::UserOnly;
        $ItemsTypes->patch(Action::Update, array(
            'canread_target_base' => $canreadTarget->value,
            'canwrite_target_base' => $canwriteTarget->value,
        ));
        // now create an item from that template
        $newId = $this->Items->postAction(Action::Create, array('template' => $itemTemplate));
        $this->assertIsInt($newId);
        $this->Items->setId($newId);
        $this->assertEquals($canreadTarget->value, $this->Items->entityData['canread_base']);
        $this->assertEquals($canwriteTarget->value, $this->Items->entityData['canwrite_base']);
    }

    public function testToggleLock(): void
    {
        $new = $this->Items->create(tags: array('locked'));
        $this->Items->setId($new);

        // lock
        $item = $this->Items->toggleLock();
        $this->assertTrue((bool) $item['locked']);
        // unlock
        $item = $this->Items->toggleLock();
        $this->assertFalse((bool) $item['locked']);
    }

    // test metadata merge preserves existing fields on import
    public function testMetadataMergePreservesExistingFieldSchema(): void
    {
        $new = $this->Items->create();
        $this->Items->setId($new);

        $baseMetadata = json_encode(array(
            'extra_fields' => array(
                'weight' => array(
                    'type' => 'number',
                    'value' => '0',
                    'units' => array('g'),
                    'unit' => 'g',
                ),
                'certified' => array(
                    'type' => 'checkbox',
                    'value' => '',
                ),
                'choice' => array(
                    'type' => 'select',
                    'value' => 'A',
                    'options' => array('A', 'B'),
                ),
                'person in charge' => array(
                    'type' => 'users',
                    'value' => '',
                    'description' => 'Select the person responsible.',
                ),
                'website' => array(
                    'type' => 'url',
                    'value' => '',
                ),
            ),
        ), JSON_THROW_ON_ERROR);

        $incomingMetadata = json_encode(array(
            'extra_fields' => array(
                'weight' => array(
                    'type' => 'text',
                    'value' => '12,5',
                ),
                'certified' => array(
                    'type' => 'text',
                    'value' => 'X',
                ),
                'choice' => array(
                    'type' => 'text',
                    'value' => 'C',
                ),
                'person in charge' => array(
                    'type' => 'text',
                    'value' => '42',
                ),
                'website' => array(
                    'type' => 'text',
                    'value' => 'https://example.org',
                ),
                'new checkbox' => array(
                    'type' => 'checkbox',
                    'value' => 'oui',
                ),
                'new text' => array(
                    'type' => 'text',
                    'value' => 'hello',
                ),
            ),
        ), JSON_THROW_ON_ERROR);

        // have the base Metadata on the item
        $this->Items->patch(Action::Update, array('metadata' => $baseMetadata));
        // now merge with some incoming data
        $this->Items->patch(Action::Update, array('metadatamerge' => $incomingMetadata));

        $metadata = json_decode($this->Items->readOne()['metadata'], true, 512, JSON_THROW_ON_ERROR);
        $fields = $metadata['extra_fields'];

        // existing "weight" number keeps type/unit and receives normalized value.
        $this->assertSame('number', $fields['weight']['type']);
        $this->assertSame('12.5', $fields['weight']['value']);
        $this->assertSame(array('g'), $fields['weight']['units']);
        $this->assertSame('g', $fields['weight']['unit']);

        // existing checkbox keeps type and receives normalized truthy value.
        $this->assertSame('checkbox', $fields['certified']['type']);
        $this->assertSame('on', $fields['certified']['value']);

        // existing select keeps options even if incoming value is new.
        $this->assertSame('select', $fields['choice']['type']);
        $this->assertSame('C', $fields['choice']['value']);
        $this->assertSame(array('A', 'B'), $fields['choice']['options']);

        // existing user field keeps description/schema.
        $this->assertSame('users', $fields['person in charge']['type']);
        $this->assertSame('42', $fields['person in charge']['value']);
        $this->assertSame('Select the person responsible.', $fields['person in charge']['description']);

        // url keeps type.
        $this->assertSame('url', $fields['website']['type']);
        $this->assertSame('https://example.org', $fields['website']['value']);

        // New fields are added and normalized according to their incoming type.
        $this->assertSame('checkbox', $fields['new checkbox']['type']);
        $this->assertSame('on', $fields['new checkbox']['value']);
        $this->assertSame('text', $fields['new text']['type']);
        $this->assertSame('hello', $fields['new text']['value']);
    }

    private function makeItemFromImmutableTemplateFor(AuthenticatedUser $user): Items
    {
        $ItemsTypes = new ItemsTypes($user);
        $templateId = $ItemsTypes->create(title: 'A resource template');
        $ItemsTypes->setId($templateId);
        $ItemsTypes->patch(Action::Update, array('canread_is_immutable' => 1));
        $Items = new Items($user);
        $newId = $Items->postAction(Action::Create, array('template' => $templateId));
        $Items->setId($newId);
        return $Items;
    }
}
