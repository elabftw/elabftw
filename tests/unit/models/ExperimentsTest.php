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
use Elabftw\Enums\FileFromString;
use Elabftw\Enums\Meaning;
use Elabftw\Enums\AccessType;
use Elabftw\Enums\State;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnprocessableContentException;
use Elabftw\Models\Users\AnonymousUser;
use Elabftw\Models\Users\Users;
use Elabftw\Params\DisplayParams;
use Elabftw\Params\EntityParams;
use Elabftw\Params\ExtraFieldsOrderingParams;
use Elabftw\Services\Check;
use Elabftw\Traits\TestsUtilsTrait;
use Symfony\Component\HttpFoundation\InputBag;

use function bin2hex;
use function count;
use function is_array;
use function json_decode;
use function random_bytes;
use function sprintf;

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

    public function testCreateAndDestroy(): void
    {
        $new = $this->Experiments->create();
        $this->assertTrue((bool) Check::id($new));
        $this->Experiments->setId($new);
        $this->Experiments->canOrExplode(AccessType::Write);
        // test archive too
        $exp = $this->Experiments->patch(Action::Archive, array());
        $this->assertIsArray($exp);
        $this->assertEquals(State::Archived->value, $exp['state']);
        $this->assertEquals(1, $exp['locked'], 'Entity should be locked when archived');
        // unarchive (should also unlock)
        $exp = $this->Experiments->patch(Action::Unarchive, array());
        $this->assertIsArray($exp);
        $this->assertEquals(State::Normal->value, $exp['state']);
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
        $tpl = $Templates->create(title: 'my template');
        $new = $this->Experiments->postAction(Action::Create, array('template' => $tpl));
        $this->assertTrue((bool) Check::id($new));
        $newExp = new Experiments($this->Users, $new);
        $this->assertTrue($newExp->destroy());
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
        $this->Experiments->canOrExplode(AccessType::Read);
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
        $matrix = array('canread_base', 'canwrite_base');
        foreach ($matrix as $column) {
            foreach (BasePermissions::cases() as $perm) {
                $this->assertIsArray($this->Experiments->patch(Action::Update, array($column => $perm->value)));
            }
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
        $params = array('userid' => $user2->userid, 'team' => $user2->getTeam());
        // no readOne after ownership change
        $exp->patch(Action::UpdateOwner, $params);
        $entityData = $exp->readOne();
        $this->assertEquals($user2->userid, $entityData['userid']);
        $this->assertEquals($user2->team, $entityData['team']);
    }

    public function testUpdateOwnershipWithoutUsingDedicatedAction(): void
    {
        $User = $this->getRandomUserInTeam(1);
        $exp = $this->getFreshExperimentWithGivenUser($User);
        $params = array('userid' => $User->getUserid(), 'team' => $User->getTeam());
        // needs to use Action::UpdateOwner if we're using userid/team params)
        $this->expectException(ImproperActionException::class);
        $exp->patch(Action::Update, $params);
    }

    public function testUpdateOwnershipWrongTeamCombination(): void
    {
        $user1 = new Users(1, 1);
        $user2 = new Users(2, 2);
        $exp = $this->getFreshExperimentWithGivenUser($user1);
        $params = array('userid' => $user2->userid, 'team' => 17);
        $this->expectException(UnprocessableContentException::class);
        $exp->patch(Action::UpdateOwner, $params);
    }

    public function testUpdateOwnershipToDifferentTeamIsRestrictedToAdmins(): void
    {
        $user1 = new Users(1, 1);
        $user1->isAdmin = false;
        $user2 = new Users(2, 2);
        $exp = $this->getFreshExperimentWithGivenUser($user1);
        $this->expectException(IllegalActionException::class);
        $exp->patch(Action::UpdateOwner, array('userid' => $user2->userid, 'team' => 2));
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
        $firstCustomId = $patched['custom_id'];
        $this->assertGreaterThan(0, $firstCustomId);
        $patched = $this->Experiments->patch(Action::SetNextCustomId, array());
        $this->assertSame($firstCustomId, $patched['custom_id']);
        // now with another one of the same category
        $fresh = $this->getFreshExperiment();
        $fresh->patch(Action::Update, array('category' => 1));
        $patched = $fresh->patch(Action::SetNextCustomId, array());
        $this->assertSame($firstCustomId + 1, $patched['custom_id']);
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
        $linkTargetExperimentId = $this->Experiments->create();
        $this->Experiments->ExperimentsLinks->setId($linkTargetExperimentId);
        $this->Experiments->canOrExplode(AccessType::Read);
        // add specific permissions so we can check it later in the duplicated entry
        $canread = BasePermissions::Organization;
        $canwrite = BasePermissions::UserOnly;
        // also add some custom settings like hiding main text
        $this->Experiments->patch(Action::Update, array('canread_base' => $canread->value, 'canwrite_base' => $canwrite->value, 'hide_main_text' => 1));
        // add some steps and links in there, too
        new Steps($this->Experiments)->postAction(Action::Create, array('body' => 'some step'));
        $this->Experiments->ExperimentsLinks->postAction(Action::Create, array());
        // add some uploads
        $this->Experiments->Uploads->createFromString(FileFromString::Json, 'normal.json', '{}');
        $archivedId = $this->Experiments->Uploads->createFromString(FileFromString::Json, 'archived.json', '{}');
        $this->Experiments->Uploads->setId($archivedId);
        $this->Experiments->Uploads->patch(Action::Archive, array());
        $id = $this->Experiments->postAction(Action::Duplicate, array('copyFiles' => 1));
        $this->assertIsInt($id);
        $new = new Experiments($this->Users, $id);
        $this->assertEquals($canread->value, $new->entityData['canread_base']);
        $this->assertEquals($canwrite->value, $new->entityData['canwrite_base']);
        $this->assertEquals(1, $new->entityData['hide_main_text']);
        // only active files are duplicated
        $this->assertCount(1, $new->Uploads->readAll());
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

    public function testGetTimestampLastMonth(): void
    {
        $before = $this->Experiments->getTimestampLastMonth();
        $this->Experiments->timestamp();
        $this->assertSame($before + 1, $this->Experiments->getTimestampLastMonth());
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

    public function testUpdateJsonFieldWithMultipleTextValues(): void
    {
        $metadata = '{"extra_fields": {"multitext": {"type": "text", "value": ["old"], "allow_multi_values": true}}}';
        $this->Experiments->patch(Action::Update, array('metadata' => $metadata));

        // arrays are also accepted for non-select field types
        $res = $this->Experiments->patch(Action::UpdateMetadataField, array('action' => Action::UpdateMetadataField->value, 'multitext' => array('first', 'second')));
        $decoded = json_decode($res['metadata'], true);
        $this->assertEquals(array('first', 'second'), $decoded['extra_fields']['multitext']['value']);
    }

    public function testUpdateJsonFieldWithMultipleCompoundValues(): void
    {
        $metadata = '{"extra_fields": {"components": {"type": "compounds", "value": [], "allow_multi_values": true}}}';
        $this->Experiments->patch(Action::Update, array('metadata' => $metadata));

        $res = $this->Experiments->patch(Action::UpdateMetadataField, array(
            'action' => Action::UpdateMetadataField->value,
            'components' => array(424242, 424243),
        ));
        $decoded = json_decode($res['metadata'], true);
        $this->assertSame(array(424242, 424243), $decoded['extra_fields']['components']['value']);
    }

    public function testUpdateJsonFieldNormalizesValue(): void
    {
        $metadata = '{"extra_fields": {"quantity": {"type": "number", "value": ""}}}';
        $this->Experiments->patch(Action::Update, array('metadata' => $metadata));

        $res = $this->Experiments->patch(Action::UpdateMetadataField, array(
            'action' => Action::UpdateMetadataField->value,
            'quantity' => '12,5',
        ));
        $decoded = json_decode($res['metadata'], true);
        $this->assertSame('12.5', $decoded['extra_fields']['quantity']['value']);
    }

    public function testUpdateJsonFieldRejectsMultipleValuesForSingleField(): void
    {
        $metadata = '{"extra_fields": {"test": {"type": "text", "value": "old"}}}';
        $this->Experiments->patch(Action::Update, array('metadata' => $metadata));

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Metadata field test expects a single value.');
        $this->Experiments->patch(Action::UpdateMetadataField, array(
            'action' => Action::UpdateMetadataField->value,
            'test' => array('first', 'second'),
        ));
    }

    public function testUpdateJsonFieldRejectsInvalidSelectOption(): void
    {
        $metadata = '{"extra_fields": {"choice": {"type": "select", "value": "A", "options": ["A", "B"]}}}';
        $this->Experiments->patch(Action::Update, array('metadata' => $metadata));

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Metadata field choice contains an invalid option.');
        $this->Experiments->patch(Action::UpdateMetadataField, array(
            'action' => Action::UpdateMetadataField->value,
            'choice' => 'C',
        ));
    }

    public function testUpdateJsonFieldRejectsNestedMultipleValues(): void
    {
        $metadata = '{"extra_fields": {"multitext": {"type": "text", "value": [], "allow_multi_values": true}}}';
        $this->Experiments->patch(Action::Update, array('metadata' => $metadata));

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Metadata field multitext contains an invalid value.');
        $this->Experiments->patch(Action::UpdateMetadataField, array(
            'action' => Action::UpdateMetadataField->value,
            'multitext' => array(array('nested')),
        ));
    }

    public function testUpdateJsonFieldRejectsSelfLinkInMultipleValues(): void
    {
        $metadata = '{"extra_fields": {"related": {"type": "experiments", "value": [], "allow_multi_values": true}}}';
        $this->Experiments->patch(Action::Update, array('metadata' => $metadata));

        $this->expectException(ImproperActionException::class);
        $this->Experiments->patch(Action::UpdateMetadataField, array(
            'action' => Action::UpdateMetadataField->value,
            'related' => array(0, $this->Experiments->id),
        ));
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

    // focused regression test for nullable filters to ensure mixed `NULL` and `IN (...)` conditions are grouped as a single SQL predicate.
    public function testNullableCategoryFilterSqlIsGrouped(): void
    {
        $query = new InputBag(array('category' => 'null,1'));
        $DisplayParams = new DisplayParams($this->Users, EntityType::Experiments, $query);
        $expected = 'AND (entity.category IS NULL OR entity.category IN (1))';
        $this->assertStringContainsString($expected, $DisplayParams->getFilterSql());
    }

    // test anonymous user can only read Entries with 'Permission:Full' (Everyone including anonymous users)
    public function testReadAllForAnonymousUser(): void
    {
        // create experiments for each base permission
        foreach (BasePermissions::cases() as $permission) {
            $Experiments = $this->getFreshExperimentWithGivenUser($this->Users);
            $Experiments->patch(Action::Update, array('canread_base' => $permission->value));
        }

        $AnonymousUser = new AnonymousUser(1);
        $Experiments = new Experiments($AnonymousUser);
        $experiments = $Experiments->readAll();

        foreach ($experiments as $experiment) {
            $this->assertSame(BasePermissions::Full->value, $experiment['canread_base']);
        }
    }

    public function testFastqForAnonymousUserOnlyReturnsPublicEntries(): void
    {
        $titlePrefix = 'anonymous-fastq-' . bin2hex(random_bytes(8));

        foreach (BasePermissions::cases() as $permission) {
            $Experiments = $this->getFreshExperimentWithGivenUser(
                $this->Users,
            );
            $Experiments->patch(
                Action::Update,
                array(
                    'title' => sprintf(
                        '%s-%d',
                        $titlePrefix,
                        $permission->value,
                    ),
                    'canread_base' => $permission->value,
                ),
            );
        }

        $AnonymousUser = new AnonymousUser(1);
        $AnonymousExperiments = new Experiments($AnonymousUser);
        $DisplayParams = new DisplayParams(
            $AnonymousUser,
            EntityType::Experiments,
            new InputBag(array('fastq' => $titlePrefix)),
        );

        $results = $AnonymousExperiments->readAll($DisplayParams);

        self::assertCount(1, $results);
        self::assertSame(
            sprintf('%s-%d', $titlePrefix, BasePermissions::Full->value),
            $results[0]['title'],
        );
    }
}
