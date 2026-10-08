<?php

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\CreateUploadFromLocalFile;
use Elabftw\Enums\Action;
use Elabftw\Traits\TestsUtilsTrait;
use PHPUnit\Framework\TestCase;

use function dirname;

final class UploadGroupsTest extends TestCase
{
    use TestsUtilsTrait;

    private Experiments $Experiments;

    private UploadGroups $UploadGroups;

    protected function setUp(): void
    {
        $this->Experiments = $this->getFreshExperiment();
        $this->UploadGroups = new UploadGroups($this->Experiments);
    }

    public function testCreateAssignRenameAndDestroy(): void
    {
        $groupId = $this->UploadGroups->postAction(Action::Create, array('title' => 'Raw data'));
        $uploadId = $this->Experiments->Uploads->create(
            new CreateUploadFromLocalFile('example.png', dirname(__DIR__, 2) . '/_data/example.png'),
            groupId: $groupId,
        );

        $upload = new Uploads($this->Experiments, $uploadId)->readOne();
        $this->assertSame($groupId, (int) $upload['group_id']);

        $group = new UploadGroups($this->Experiments, $groupId);
        $updated = $group->patch(Action::Update, array('title' => 'Processed data'));
        $this->assertSame('Processed data', $updated['title']);

        $this->assertTrue($group->destroy());
        $upload = new Uploads($this->Experiments, $uploadId)->readOne();
        $this->assertNull($upload['group_id']);
    }

    public function testDefaultGroupIsFirstAndUploadsCanMoveBetweenGroups(): void
    {
        $firstGroup = $this->UploadGroups->postAction(Action::Create, array('title' => 'First'));
        $secondGroup = $this->UploadGroups->postAction(Action::Create, array('title' => 'Second'));
        $path = dirname(__DIR__, 2) . '/_data/example.png';
        $defaultUpload = $this->Experiments->Uploads->create(new CreateUploadFromLocalFile('default.png', $path));
        $firstUpload = $this->Experiments->Uploads->create(new CreateUploadFromLocalFile('first.png', $path), groupId: $firstGroup);
        $secondUpload = $this->Experiments->Uploads->create(new CreateUploadFromLocalFile('second.png', $path), groupId: $secondGroup);

        $this->Experiments->Uploads->patch(Action::Update, array('grouped_ordering' => array(
            array('group_id' => null, 'upload_ids' => array($defaultUpload)),
            array('group_id' => $firstGroup, 'upload_ids' => array($firstUpload)),
            array('group_id' => $secondGroup, 'upload_ids' => array($secondUpload)),
        )));

        $uploads = $this->Experiments->Uploads->readAll();
        $this->assertSame($defaultUpload, (int) $uploads[0]['id']);
        $this->assertSame($firstUpload, (int) $uploads[1]['id']);
        $this->assertSame($secondUpload, (int) $uploads[2]['id']);

        $this->UploadGroups->patch(Action::Update, array('ordering' => array($secondGroup, $firstGroup)));
        $uploads = $this->Experiments->Uploads->readAll();
        $this->assertSame($defaultUpload, (int) $uploads[0]['id']);
        $this->assertSame($secondUpload, (int) $uploads[1]['id']);
        $this->assertSame($firstUpload, (int) $uploads[2]['id']);
    }

    public function testGroupsAreDuplicatedWithUploads(): void
    {
        $groupId = $this->UploadGroups->postAction(Action::Create, array('title' => 'Microscopy'));
        $this->Experiments->Uploads->create(
            new CreateUploadFromLocalFile('example.png', dirname(__DIR__, 2) . '/_data/example.png'),
            groupId: $groupId,
        );
        $Target = $this->getFreshExperiment();

        $this->Experiments->Uploads->duplicate($Target);

        $groups = new UploadGroups($Target)->readAll();
        $uploads = $Target->Uploads->readAll();
        $this->assertCount(1, $groups);
        $this->assertSame('Microscopy', $groups[0]['title']);
        $this->assertSame((int) $groups[0]['id'], (int) $uploads[0]['group_id']);
    }

    public function testChangelogDescribesGroupChanges(): void
    {
        $groupId = $this->UploadGroups->postAction(Action::Create, array('title' => 'Preparation'));
        $Group = new UploadGroups($this->Experiments, $groupId);
        $Group->patch(Action::Update, array('title' => 'Sample preparation'));
        $this->UploadGroups->patch(Action::Update, array('ordering' => array($groupId)));
        $this->assertTrue($Group->destroy());

        $entries = array_column((new Changelog($this->Experiments))->readAll(), 'content');
        $this->assertContains(sprintf('Created upload group "Preparation" with id: %d', $groupId), $entries);
        $this->assertContains(sprintf('Renamed upload group with id: %d from "Preparation" to "Sample preparation"', $groupId), $entries);
        $this->assertContains('Reordered upload groups', $entries);
        $this->assertContains(sprintf('Deleted upload group "Sample preparation" with id: %d', $groupId), $entries);
    }
}
