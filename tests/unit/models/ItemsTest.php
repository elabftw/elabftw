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
use Elabftw\Services\Check;

use function date;

class ItemsTest extends \PHPUnit\Framework\TestCase
{
    private Items $Items;

    protected function setUp(): void
    {
        $this->Items = new Items(new Users(1, 1));
    }

    public function testCreateAndDestroy(): void
    {
        $new = $this->Items->create(template: 1);
        $this->assertTrue((bool) Check::id($new));
        $this->Items->setId($new);
        $this->Items->destroy();
    }

    public function testRead(): void
    {
        $new = $this->Items->create(template: 1);
        $this->Items->setId($new);
        $this->Items->canOrExplode('read');
        $this->assertTrue(is_array($this->Items->entityData));
        $this->assertEquals('Untitled', $this->Items->entityData['title']);
        $this->assertEquals(date('Y-m-d'), $this->Items->entityData['date']);
    }

    public function testUpdate(): void
    {
        $new = $this->Items->create(template: 1);
        $this->Items->setId($new);
        $entityData = $this->Items->patch(Action::Update, array('title' => 'Untitled', 'date' => '20160729', 'body' => '<p>Body</p>'));
        $this->assertEquals('Untitled', $entityData['title']);
        $this->assertEquals('2016-07-29', $entityData['date']);
        $this->assertEquals('<p>Body</p>', $entityData['body']);
    }

    public function testReadBookable(): void
    {
        $this->assertIsArray($this->Items->readBookable());
    }

    public function testCanBookInPast(): void
    {
        // use a normal user
        $Items = new Items(new Users(2, 1));
        $new = $Items->create(template: 1);
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
        $this->Items->setId(1);
        $this->Items->canOrExplode('read');
        $ItemsTypes = new ItemsTypes($this->Items->Users);
        $category = $ItemsTypes->create(title: 'Used in tests');
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
        $category = $ItemsTypes->create(title: 'Used in tests');
        $ItemsTypes->setId($category);
        // set permissions on template
        $canreadTarget = BasePermissions::Organization->toJson();
        $canwriteTarget = BasePermissions::UserOnly->toJson();
        $ItemsTypes->patch(Action::Update, array(
            'canread_target' => $canreadTarget,
            'canwrite_target' => $canwriteTarget,
        ));
        // now create an item from that template
        $newId = $this->Items->create(template: $category);
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
        $new = $this->Items->create(template: 1, tags: array('locked'));
        $this->Items->setId($new);

        // lock
        $item = $this->Items->toggleLock();
        $this->assertTrue((bool) $item['locked']);
        // unlock
        $item = $this->Items->toggleLock();
        $this->assertFalse((bool) $item['locked']);
    }
}
