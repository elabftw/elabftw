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
use Elabftw\Enums\State;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Exceptions\UnprocessableContentException;
use Elabftw\Models\Users\Users;
use Elabftw\Params\DisplayParams;
use Elabftw\Params\EntityParams;
use Elabftw\Params\ExtraFieldsOrderingParams;
use Elabftw\Services\Check;
use Elabftw\Traits\TestsUtilsTrait;
use Symfony\Component\HttpFoundation\InputBag;

class ExperimentsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Users $Users;

    private Experiments $Experiments;

    protected function setUp(): void
    {
        $this->Users = $this->getRandomUserInTeam(1);
        $this->Experiments = $this->getFreshExperimentWithGivenUser($this->Users);
    }

    public function testGetSurroundingBookers(): void
    {
        $this->assertEmpty($this->Experiments->getSurroundingBookers());
    }

    public function testCreateAndDestroy(): void
    {
        $new = $this->Experiments->create();
        $this->assertTrue((bool) Check::id($new));
        $this->Experiments->setId($new);
        $this->Experiments->canOrExplode('write');
        // test archive too
        $exp = $this->Experiments->patch(Action::Archive, array());
        $this->assertIsArray($exp);
        $this->assertEquals(State::Archived->value, $exp['state']);
        $this->assertEquals(1, $exp['locked'], 'Entity should be locked when archived');
        // unarchive (should also unlock)
        $exp = $this->Experiments->patch(Action::Unarchive, array());
        $this->assertIsArray($exp);
        $this->assertEquals(State::Normal->value, $exp['state']);
        $this->assertEquals(0, $exp['locked'], 'Entity should be unlocked when unarchived');
        // lock
        $exp = $this->Experiments->lock();
        $this->assertEquals(1, $exp['locked']);
        // unlock
        $exp = $this->Experiments->unlock();
        $this->assertEquals(0, $exp['locked']);
        // toggle locks
        $exp = $this->Experiments->toggleLock();
        $this->assertEquals(1, $exp['locked']);
        $exp = $this->Experiments->toggleLock();
        $this->assertEquals(0, $exp['locked']);
        // test delete
        $exp = $this->Experiments->patch(Action::Destroy, array());
        $this->assertEquals(State::Deleted->value, $exp['state']);
        // test restore
        $exp = $this->Experiments->patch(Action::Restore, array());
        $this->assertEquals(State::Normal->value, $exp['state']);
        $this->Experiments->destroy();
        $Templates = new Templates($this->Users);
        $Templates->create(title: 'my template');
        $new = $this->Experiments->createFromTemplate(1);
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
        $title = 'uP75wAqLqTXxqnxSK5CDDyniHFfj';
        $query = new InputBag(array('q' => $title));
        $DisplayParams = new DisplayParams($this->Users, EntityType::Experiments, $query);
        $all = $this->Experiments->readAll($DisplayParams);
        // first search for it before creating it
        $this->assertTrue(empty($all));
        // then create it so we can find it with a search
        $new = $this->Experiments->create(title: $title);
        $all = $this->Experiments->readAll($DisplayParams);
        $this->assertEquals(1, count($all));
        $this->Experiments->setId($new);
        $this->Experiments->canOrExplode('read');
        $experiment = $this->Experiments->readOne();
        $this->assertTrue(is_array($experiment));
        $this->assertEquals($title, $experiment['title']);
        $this->assertEquals(State::Normal->value, $experiment['state']);
        // do a fastq read
        $DisplayParams->getQuery()->add(array('fastq' => 1));
        $fast = $this->Experiments->readAll($DisplayParams);
        $this->assertNotEmpty($fast);
    }

    public function testUpdate(): void
    {
        $new = $this->Experiments->create();
        $this->Experiments->setId($new);
        $this->assertEquals($new, $this->Experiments->id);
        $this->assertEquals($this->Users->userid, $this->Experiments->Users->userData['userid']);
        $entityData = $this->Experiments->patch(Action::Update, array('title' => 'Untitled', 'date' => '20160729', 'body' => '<p>Body</p>'));
        $this->assertEquals('Untitled', $entityData['title']);
        $this->assertEquals('2016-07-29', $entityData['date']);
        $this->assertEquals('<p>Body</p>', $entityData['body']);
    }

    public function testUpdateIncorrectState(): void
    {
        $new = $this->Experiments->create();
        $this->Experiments->setId($new);
        $this->expectException(ImproperActionException::class);
        $this->Experiments->update(new EntityParams('state', '42'));
    }

    public function testCannotUpdateDeletedExperiment(): void
    {
        $new = $this->Experiments->create();
        $this->Experiments->setId($new);
        $this->Experiments->patch(Action::Update, array('state' => State::Deleted->value));
        $this->assertEquals(State::Deleted->value, $this->Experiments->entityData['state']);
        // Any other action than Action::Restore returns an UnprocessableContent
        $this->expectException(UnprocessableContentException::class);
        $this->Experiments->patch(Action::Update, array('title' => 'Changed title'));
    }

    public function testCannotUpdateArchivedExperiment(): void
    {
        $new = $this->Experiments->create();
        $this->Experiments->setId($new);
        $this->Experiments->patch(Action::Update, array('state' => State::Archived->value));
        $this->assertEquals(State::Archived->value, $this->Experiments->entityData['state']);
        // Any other action than Action::Unarchive returns an UnprocessableContent
        $this->expectException(UnprocessableContentException::class);
        $this->Experiments->patch(Action::Timestamp, array());
    }

    public function testUpdateVisibility(): void
    {
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
        $this->assertIsArray($this->Experiments->patch(Action::Update, array('category' => '3')));
    }

    public function testUpdateOwnership(): void
    {
        $user1 = new Users(1, 1);
        $user2 = new Users(2, 1);
        $exp = $this->getFreshExperimentWithGivenUser($user1);
        $params = array('users_experiments' => array($user1->userid), 'userid' => $user2->userid);
        $exp->patch(Action::UpdateOwner, $params);
        $this->assertEquals($exp->entityData['userid'], $user2->userid);
        $this->assertEquals($exp->entityData['team'], $user2->team);
    }

    public function testUpdateOwnershipToDifferentTeamIsRestrictedToAdmins(): void
    {
        $user1 = new Users(1, 1);
        $user1->isAdmin = false;
        $user2 = new Users(2, 2);
        $exp = $this->getFreshExperimentWithGivenUser($user1);
        $params = array('users_experiments' => array($user1->userid), 'userid' => $user2->userid, 'team' => $user2->team);
        $this->expectException(UnauthorizedException::class);
        $exp->patch(Action::UpdateOwner, $params);
    }

    public function testUpdateWithNegativeInt(): void
    {
        $this->assertIsArray($this->Experiments->patch(Action::Update, array('category' => '-3', 'custom_id' => '-5')));
        $this->assertNull($this->Experiments->entityData['category']);
        $this->assertNull($this->Experiments->entityData['custom_id']);
    }

    public function testCustomId(): void
    {
        // give it a category
        $this->Experiments->patch(Action::Update, array('category' => 1));
        $patched = $this->Experiments->patch(Action::SetNextCustomId, array());
        $this->assertSame(1, $patched['custom_id']);
        $patched = $this->Experiments->patch(Action::SetNextCustomId, array());
        $this->assertSame(1, $patched['custom_id']);
        // now with another one of the same category
        $fresh = $this->getFreshExperiment();
        $fresh->patch(Action::Update, array('category' => 1));
        $patched = $fresh->patch(Action::SetNextCustomId, array());
        $this->assertSame(2, $patched['custom_id']);
        // now get an exception without a category set
        $this->expectException(ImproperActionException::class);
        $this->getFreshExperiment()->patch(Action::SetNextCustomId, array());
    }

    public function testSign(): void
    {
        // we need to generate a key
        $passphrase = 'correct horse battery staple';
        $SigKeys = new SigKeys($this->Experiments->Users);
        $SigKeys->postAction(Action::Create, array('passphrase' => $passphrase));
        // reload the Users object because we now have a key
        $this->Experiments->Users->readOne();
        $this->assertIsArray($this->Experiments->patch(Action::Sign, array(
            'passphrase' => $passphrase,
            'meaning' => (string) Meaning::Responsibility->value,
        )));
    }

    public function testDuplicate(): void
    {
        $this->Experiments->ItemsLinks->setId(1);
        $this->Experiments->ExperimentsLinks->setId(1);
        $this->Experiments->canOrExplode('read');
        // add specific permissions so we can check it later in the duplicated entry
        $canread = BasePermissions::Organization->toJson();
        $canwrite = BasePermissions::UserOnly->toJson();
        // also add some custom settings like hiding main text
        $this->Experiments->patch(Action::Update, array('canread' => $canread, 'canwrite' => $canwrite, 'hide_main_text' => 1));
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
        $this->assertEquals(1, $new->entityData['hide_main_text']);
    }

    public function testInsertTags(): void
    {
        $this->assertIsInt($this->Experiments->create(tags: array('tag-bbbtbtbt', 'tag-auristearuiset')));
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
        // create some metadata first
        $metadata = '{"extra_fields": {"test": {"type": "text", "value": "%s"}, "multiselect": {"type": "select", "value": ["val1", "val2", "val3"], "options": ["val1", "val2", "val3", "val4"], "allow_multi_values": true}}}';
        $this->Experiments->patch(Action::Update, array('metadata' => $metadata));
        // now update ordering of fields
        $OrderingParams = new ExtraFieldsOrderingParams(array(
            'entity' => array('type' => EntityType::Experiments->value, 'id' => '123'),
            'ordering' => array('multiselect', 'test'),
            'table' => 'extra_fields',
        ));
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
