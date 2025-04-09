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
use Elabftw\Enums\Meaning;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Params\EntityParams;
use Elabftw\Params\ExtraFieldsOrderingParams;
use Elabftw\Services\Check;

class ExperimentsTest extends \PHPUnit\Framework\TestCase
{
    private Users $Users;

    private Experiments $Experiments;

    protected function setUp(): void
    {
        $this->Users = new Users(1, 1);
        $this->Experiments = new Experiments($this->Users);
    }

    public function testCreateAndDestroy(): void
    {
        $new = $this->Experiments->create(template: 0);
        $this->assertTrue((bool) Check::id($new));
        $this->Experiments->setId($new);
        $this->Experiments->canOrExplode('write');
        // test archive too
        $this->assertIsArray($this->Experiments->patch(Action::Archive, array()));
        // two times to test unarchive branch
        $this->assertIsArray($this->Experiments->patch(Action::Archive, array()));
        $this->Experiments->toggleLock();
        $this->Experiments->destroy();
        $Templates = new Templates($this->Users);
        $Templates->create(title: 'my template');
        $new = $this->Experiments->create(template: 1);
        $this->assertTrue((bool) Check::id($new));
        $this->Experiments = new Experiments($this->Users, $new);
        $this->Experiments->destroy();
    }

    public function testSetId(): void
    {
        $this->expectException(IllegalActionException::class);
        $this->Experiments->setId(0);
    }

    public function testRead(): void
    {
        $new = $this->Experiments->create(template: 0);
        $this->Experiments->setId($new);
        $this->Experiments->canOrExplode('read');
        $experiment = $this->Experiments->readOne();
        $this->assertTrue(is_array($experiment));
        $this->assertEquals('Untitled', $experiment['title']);
    }

    public function testUpdate(): void
    {
        $new = $this->Experiments->create(template: 0);
        $this->Experiments->setId($new);
        $this->assertEquals($new, $this->Experiments->id);
        $this->assertEquals(1, $this->Experiments->Users->userData['userid']);
        $entityData = $this->Experiments->patch(Action::Update, array('title' => 'Untitled', 'date' => '20160729', 'body' => '<p>Body</p>'));
        $this->assertEquals('Untitled', $entityData['title']);
        $this->assertEquals('2016-07-29', $entityData['date']);
        $this->assertEquals('<p>Body</p>', $entityData['body']);
    }

    public function testUpdateIncorrectState(): void
    {
        $new = $this->Experiments->create(template: 0);
        $this->Experiments->setId($new);
        $this->expectException(ImproperActionException::class);
        $this->Experiments->update(new EntityParams('state', '42'));
    }

    public function testUpdateVisibility(): void
    {
        $this->Experiments->setId(1);
        $matrix = array('canread', 'canwrite');
        foreach ($matrix as $column) {
            $this->assertIsArray($this->Experiments->patch(Action::Update, array($column => BasePermissions::Full->toJson())));
            $this->assertIsArray($this->Experiments->patch(Action::Update, array($column => BasePermissions::Organization->toJson())));
            $this->assertIsArray($this->Experiments->patch(Action::Update, array($column => BasePermissions::Team->toJson())));
            $this->assertIsArray($this->Experiments->patch(Action::Update, array($column => BasePermissions::User->toJson())));
            $this->assertIsArray($this->Experiments->patch(Action::Update, array($column => BasePermissions::UserOnly->toJson())));
        }
    }

    public function testUpdateCategory(): void
    {
        $this->Experiments->setId(1);
        $this->assertIsArray($this->Experiments->patch(Action::Update, array('category' => '3')));
    }

    public function testUpdateWithNegativeInt(): void
    {
        $this->Experiments->setId(1);
        $this->assertIsArray($this->Experiments->patch(Action::Update, array('category' => '-3', 'custom_id' => '-5')));
        $this->assertNull($this->Experiments->entityData['category']);
        $this->assertNull($this->Experiments->entityData['custom_id']);
    }

    public function testSign(): void
    {
        $this->Experiments->setId(1);
        // we need to generate a key
        $passphrase = 'correct horse battery staple';
        $SigKeys = new SigKeys($this->Users);
        $SigKeys->postAction(Action::Create, array('passphrase' => $passphrase));
        // reload the Users object because we now have a key
        $this->Users->readOne();
        $this->assertIsArray($this->Experiments->patch(Action::Sign, array(
            'passphrase' => $passphrase,
            'meaning' => (string) Meaning::Responsibility->value,
        )));
    }

    public function testDuplicate(): void
    {
        $this->Experiments->setId(1);
        $this->Experiments->ItemsLinks->setId(1);
        $this->Experiments->ExperimentsLinks->setId(1);
        $this->Experiments->canOrExplode('read');
        // add specific permissions so we can check it later in the duplicated entry
        $canread = BasePermissions::Organization->toJson();
        $canwrite = BasePermissions::UserOnly->toJson();
        $this->Experiments->patch(Action::Update, array('canread' => $canread, 'canwrite' => $canwrite));
        // add some steps and links in there, too
        $this->Experiments->Steps->postAction(Action::Create, array('body' => 'some step'));
        $this->Experiments->ItemsLinks->postAction(Action::Create, array());
        $this->Experiments->ExperimentsLinks->postAction(Action::Create, array());
        $id = $this->Experiments->postAction(Action::Duplicate, array());
        $this->assertIsInt($id);
        $new = new Experiments($this->Users, $id);
        $actualCanread = json_decode($new->entityData['canread'], true);
        $actualCanwrite = json_decode($new->entityData['canwrite'], true);
        $this->assertEquals(BasePermissions::Organization->value, $actualCanread['base']);
        $this->assertEquals(BasePermissions::UserOnly->value, $actualCanwrite['base']);
    }

    public function testInsertTags(): void
    {
        $this->assertIsInt($this->Experiments->create(template: 0, tags: array('tag-bbbtbtbt', 'tag-auristearuiset')));
    }

    public function testGetTags(): void
    {
        $res = $this->Experiments->getTags(array(array('id' => 0)));
        $this->assertEmpty($res);
        $res = $this->Experiments->getTags(array(array('id' => 1), array('id' => 2)));
        $this->assertIsArray($res);
    }

    public function testGetTimestampThisMonth(): void
    {
        $this->assertEquals(4, $this->Experiments->getTimestampLastMonth());
    }

    public function testUpdateJsonField(): void
    {
        $this->Experiments->setId(1);
        // set some metadata, spaces after colons and commas are important as this is how metadata gets return from MySQL
        $metadata = '{"extra_fields": {"test": {"type": "text", "value": "%s"}, "multiselect": {"type": "select", "value": ["val1", "val2", "val3"], "options": ["val1", "val2", "val3", "val4"], "allow_multi_values": true}}}';
        $res = $this->Experiments->patch(Action::Update, array('metadata' => $metadata));
        $this->assertEquals($metadata, $res['metadata']);
        // update the field
        $res = $this->Experiments->patch(Action::UpdateMetadataField, array('action' => Action::UpdateMetadataField->value, 'test' => 'some text'));
        $this->assertEquals(sprintf($metadata, 'some text'), $res['metadata']);
        // update the multi select so we go in the is_array branch for the changelog value
        $res = $this->Experiments->patch(Action::UpdateMetadataField, array('action' => Action::UpdateMetadataField->value, 'multiselect' => array('val1', 'val2')));
        $decoded = json_decode($res['metadata'], true);
        $this->assertEquals(array('val1', 'val2'), $decoded['extra_fields']['multiselect']['value']);
    }

    public function testUpdateExtraFieldsOrdering(): void
    {
        $OrderingParams = new ExtraFieldsOrderingParams(array(
            'entity' => array('type' => EntityType::Experiments->value, 'id' => '123'),
            'ordering' => array('multiselect', 'test'),
            'table' => 'extra_fields',
        ));
        $this->Experiments->setId(1);
        $entityData = $this->Experiments->updateExtraFieldsOrdering($OrderingParams);
        $decoded = json_decode($entityData['metadata'], true);
        $this->assertEquals(0, $decoded['extra_fields']['multiselect']['position']);
        $this->assertEquals(1, $decoded['extra_fields']['test']['position']);
    }

    public function testReuseCustomId(): void
    {
        $newExperiment = $this->Experiments->create();
        $this->Experiments->setId($newExperiment);
        $this->Experiments->patch(Action::Update, array('category' => 3, 'custom_id' => 99));
        $copy = $this->Experiments->postAction(Action::Duplicate, array());
        $this->Experiments->setId($copy);
        $this->expectException(ImproperActionException::class);
        $this->Experiments->patch(Action::Update, array('custom_id' => 99));
    }
}
