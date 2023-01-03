<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\DisplayParams;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Services\Check;
use Symfony\Component\HttpFoundation\Request;

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
        $new = $this->Experiments->create(0);
        $this->assertTrue((bool) Check::id($new));
        $this->Experiments->setId($new);
        $this->Experiments->canOrExplode('write');
        $this->Experiments->toggleLock();
        $this->Experiments->destroy();
        $Templates = new Templates($this->Users);
        $Templates->create('my template');
        $new = $this->Experiments->create(1);
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
        $new = $this->Experiments->create(0);
        $this->Experiments->setId($new);
        $this->Experiments->canOrExplode('read');
        $experiment = $this->Experiments->readOne();
        $this->assertTrue(is_array($experiment));
        $this->assertEquals('Untitled', $experiment['title']);
    }

    public function testUpdate(): void
    {
        $new = $this->Experiments->create(0);
        $this->Experiments->setId($new);
        $this->assertEquals($new, $this->Experiments->id);
        $this->assertEquals(1, $this->Experiments->Users->userData['userid']);
        $entityData = $this->Experiments->patch(Action::Update, array('title' => 'Untitled', 'date' => '20160729', 'body' => '<p>Body</p>'));
        $this->assertEquals('Untitled', $entityData['title']);
        $this->assertEquals('2016-07-29', $entityData['date']);
        $this->assertEquals('<p>Body</p>', $entityData['body']);
    }

    public function testUpdateVisibility(): void
    {
        $this->Experiments->setId(1);
        $matrix = array('canread', 'canwrite');
        foreach ($matrix as $column) {
            $this->assertIsArray($this->Experiments->patch(Action::Update, array($column => BasePermissions::Full->toJson())));
            $this->assertIsArray($this->Experiments->patch(Action::Update, array($column => BasePermissions::Organization->toJson())));
            $this->assertIsArray($this->Experiments->patch(Action::Update, array($column => BasePermissions::MyTeams->toJson())));
            $this->assertIsArray($this->Experiments->patch(Action::Update, array($column => BasePermissions::User->toJson())));
            $this->assertIsArray($this->Experiments->patch(Action::Update, array($column => BasePermissions::UserOnly->toJson())));
        }
    }

    public function testUpdateCategory(): void
    {
        $this->Experiments->setId(1);
        $this->assertIsArray($this->Experiments->patch(Action::Update, array('category' => '3')));
    }

    public function testDuplicate(): void
    {
        $this->Experiments->setId(1);
        $this->Experiments->ItemsLinks->setId(1);
        $this->Experiments->ExperimentsLinks->setId(1);
        $this->Experiments->canOrExplode('read');
        // add some steps and links in there, too
        $this->Experiments->Steps->postAction(Action::Create, array('body' => 'some step'));
        $this->Experiments->ItemsLinks->postAction(Action::Create, array());
        $this->Experiments->ExperimentsLinks->postAction(Action::Create, array());
        $this->assertIsInt($this->Experiments->postAction(Action::Duplicate, array()));
    }

    public function testInsertTags(): void
    {
        $this->assertIsInt($this->Experiments->create(0, array('tag-bbbtbtbt', 'tag-auristearuiset')));
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
        $this->assertEquals(0, $this->Experiments->getTimestampLastMonth());
    }

    public function testUpdateJsonField(): void
    {
        $this->Experiments->setId(1);
        // set some metadata, spaces after colons and commas are important as this is how metadata gets return from MySQL
        $metadata = '{"extra_fields": {"test": {"type": "text", "value": "%s"}}}';
        $res = $this->Experiments->patch(Action::Update, array('metadata' => $metadata));
        $this->assertEquals($metadata, $res['metadata']);
        // update the field
        $res = $this->Experiments->patch(Action::UpdateMetadataField, array('action' => Action::UpdateMetadataField->value, 'test' => 'some text'));
        $this->assertEquals(sprintf($metadata, 'some text'), $res['metadata']);
    }

    public function testExtraFieldsSearch(): void
    {
        $request = Request::createFromGlobals();
        $request->query->add(array(
            'metakey' => array('test'),
            'metavalue' => array('some text'),
        ));
        $displayParams = new DisplayParams($this->Users, $request);
        $res = $this->Experiments->readShow($displayParams);
        $this->assertEquals(1, $res[0]['id']);
    }
}
