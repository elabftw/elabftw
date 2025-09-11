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

use DateTimeImmutable;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\FileFromString;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnprocessableContentException;
use Elabftw\Models\Users\AuthenticatedUser;
use Elabftw\Params\TeamParam;
use Elabftw\Services\Check;
use Elabftw\Traits\TestsUtilsTrait;

use function date;

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
        $new = $this->Items->createFromTemplate($templateId);
        $Items = new Items($user, $new);
        $this->assertSame($title, $Items->entityData['title']);
        $this->assertSame($body, $Items->entityData['body']);
        $this->assertEqualsCanonicalizing($tags, array_column($Items->Tags->readAll(), 'tag'));
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
        $Items->patch(Action::Update, array('canread' => BasePermissions::UserOnly->toJson()));
    }

    public function testAdminCanBypassImmutablePermissions(): void
    {
        $admin = $this->getRandomUserInTeam(1, admin: 1);
        $Items = $this->makeItemFromImmutableTemplateFor($admin);
        $Items->patch(Action::Update, array('canread' => BasePermissions::UserOnly->toJson()));
        $canRead = json_decode($Items->readOne()['canread'], true);
        $this->assertSame(1, $Items->readOne()['canread_is_immutable']);
        $this->assertEquals(BasePermissions::UserOnly->value, $canRead['base']);
    }

    public function testCannotChangeImmutabilitySettings(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $Items = $this->makeItemFromImmutableTemplateFor($user);
        $this->expectException(UnprocessableContentException::class);
        $Items->patch(Action::Update, array('canread_is_immutable' => 1));
    }

    public function testRead(): void
    {
        $new = $this->Items->create();
        $this->Items->setId($new);
        $this->Items->canOrExplode('read');
        $this->assertEquals('Untitled', $this->Items->entityData['title']);
        $this->assertEquals(date('Y-m-d'), $this->Items->entityData['date']);
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
        $this->Items->canOrExplode('read');
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
        $this->Items->canOrExplode('read');
        $ResourcesCategories = new ResourcesCategories(new Teams($this->Items->Users, $this->Items->Users->team));
        $category = $ResourcesCategories->create(title: 'Used in tests');
        $this->Items->patch(Action::Update, array('category' => $category));
        $newId = $this->Items->postAction(Action::Duplicate, array());
        $this->assertIsInt($newId);
        $this->Items->setId($newId);
        $this->assertEquals($category, $this->Items->entityData['category']);
    }

    // make sure the item inherits the permissions from the template target permissions
    public function testTemplatePermissionsReported(): void
    {
        $ItemsTypes = new ItemsTypes($this->Items->Users);
        $itemTemplate = $ItemsTypes->create(title: 'Used in tests');
        $ItemsTypes->setId($itemTemplate);
        // set permissions on template
        $canreadTarget = BasePermissions::Organization->toJson();
        $canwriteTarget = BasePermissions::UserOnly->toJson();
        $ItemsTypes->patch(Action::Update, array(
            'canread_target' => $canreadTarget,
            'canwrite_target' => $canwriteTarget,
        ));
        // now create an item from that template
        $newId = $this->Items->createFromTemplate($itemTemplate);
        $this->assertIsInt($newId);
        $this->Items->setId($newId);
        // have to decode the json because the keys won't be in the same order, so assertEquals fails
        $actualCanread = json_decode($this->Items->entityData['canread'], true);
        $actualCanwrite = json_decode($this->Items->entityData['canwrite'], true);
        $this->assertEquals(BasePermissions::Organization->value, $actualCanread['base']);
        $this->assertEquals(BasePermissions::UserOnly->value, $actualCanwrite['base']);
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

    public function testGetSurroundingBookers(): void
    {
        $item = $this->getFreshBookableItem(2);
        $Scheduler = new Scheduler($item);
        $start = new DateTimeImmutable('+3 hour');
        $end = new DateTimeImmutable('+6 hour');
        $Scheduler->postAction(Action::Create, array('start' => $start->format('c'), 'end' => $end->format('c'), 'title' => 'Mail event'));
        $result = $item->getSurroundingBookers();
        $this->assertCount(1, $result);

        // now with an unbookable item
        $item = $this->getFreshItem(2);
        $this->assertEmpty($item->getSurroundingBookers());
    }

    private function makeItemFromImmutableTemplateFor(AuthenticatedUser $user): Items
    {
        $ItemsTypes = new ItemsTypes($user);
        $templateId = $ItemsTypes->create(title: 'A resource template');
        $ItemsTypes->setId($templateId);
        $ItemsTypes->patch(Action::Update, array('canread_is_immutable' => 1, 'canread' => BasePermissions::Team->toJson()));
        $newId = $this->Items->createFromTemplate($templateId);
        return new Items($user, $newId);
    }
}
