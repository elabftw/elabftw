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

class LinksTest extends \PHPUnit\Framework\TestCase
{
    private Experiments $Experiments;

    private Items $Items;

    protected function setUp(): void
    {
        $this->Experiments = new Experiments(new Users(1, 1), 3);
        $this->Items = new Items(new Users(1, 1), 3);
        $this->Experiments->ExperimentsLinks->setId(4);
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/experiments/3/experiments2experiments/', $this->Experiments->ExperimentsLinks->getApiPath());
    }

    public function testCreateReadDestroy(): void
    {
        $this->Experiments->ItemsLinks->setId(1);
        $this->Experiments->ItemsLinks->postAction(Action::Create, array());
        $count = count($this->Experiments->ItemsLinks->readAll());
        $this->assertEquals(1, $count);
        $this->Experiments->ItemsLinks->destroy();
        $this->assertEquals(0, count($this->Experiments->ItemsLinks->readAll()));

        $this->Experiments->ExperimentsLinks->setId(4);
        $this->Experiments->ExperimentsLinks->postAction(Action::Create, array());
        $count = count($this->Experiments->ExperimentsLinks->readAll());
        $this->assertEquals(1, $count);
        $this->Experiments->ExperimentsLinks->destroy();
        $this->assertEquals(0, count($this->Experiments->ExperimentsLinks->readAll()));
    }

    public function testImport(): void
    {
        // create a link in a db item
        $Items = new Items(new Users(1, 1), 1);
        $Items->ItemsLinks->setId(1);
        $Items->ItemsLinks->postAction(Action::Create, array());
        // now import this in our experiment like if we click the import links button
        $Links = new Items2ItemsLinks($this->Experiments, $Items->id);
        $this->assertIsInt($Links->postAction(Action::Duplicate, array()));
        $this->Experiments->ItemsLinks->setId(1);
        $this->assertIsInt($this->Experiments->ItemsLinks->postAction(Action::Duplicate, array()));
        $this->Experiments->ExperimentsLinks->setId(1);
        $this->assertIsInt($this->Experiments->ExperimentsLinks->postAction(Action::Duplicate, array()));
    }

    public function testReadOne(): void
    {
        $this->assertIsArray($this->Experiments->ItemsLinks->readOne());
        $this->assertIsArray($this->Experiments->ExperimentsLinks->readOne());
    }

    public function testReadRelated(): void
    {
        $this->assertIsArray($this->Experiments->ItemsLinks->readRelated());
        $this->assertIsArray($this->Experiments->ExperimentsLinks->readRelated());
        $this->assertIsArray($this->Items->ItemsLinks->readRelated());
        $this->assertIsArray($this->Items->ExperimentsLinks->readRelated());
    }

    public function testLinksFromTemplate(): void
    {
        $Templates = new Templates(new Users(1, 1));
        // create a template
        $id = $Templates->postAction(Action::Create, array('title' => 'some template'));
        $Templates->setId($id);
        // add an item link
        $Templates->ItemsLinks->setId(1);
        $Templates->ItemsLinks->postAction(Action::Create, array());
        // now create an experiment from that template
        $expid = $this->Experiments->postAction(Action::Create, array('category_id' => $id));
        $this->Experiments->setId($expid);
        $this->assertEquals(1, count($this->Experiments->ItemsLinks->readAll()));
    }

    public function testReadExperimentsLinksFromTemplate(): void
    {
        $Templates = new Templates(new Users(1, 1), 1);
        $this->assertEmpty($Templates->ExperimentsLinks->readAll());
        $Templates->ExperimentsLinks->setId(1);
        $Templates->ExperimentsLinks->postAction(Action::Create, array());
        $this->assertEquals(1, count($Templates->ExperimentsLinks->readAll()));
    }

    public function testObeyReadPermissionAfterLinksImportWithinTeam(): void
    {
        // compare to #5523, #5524
        // In the most simple case, we stay within a given team

        // User 1 creates experiment A that is visible to the team
        $Experiments = new Experiments(new Users(1, 1));
        $ExperimentAId = $Experiments->create(
            title: 'Experiment A',
            canread: BasePermissions::Team->toJson(),
        );

        // User 1 creates experiment B that is visible only to themself
        $secretTitle = 'Experiment B - This title shall not be visible to user 2 after importing links';
        $ExperimentBId = $Experiments->create(
            title: $secretTitle,
            canread: BasePermissions::User->toJson(),
        );

        // Experiment A links to experiment B
        $Experiments->setId($ExperimentAId);
        $Experiments->ExperimentsLinks->setId($ExperimentBId);
        $Experiments->ExperimentsLinks->postAction(Action::Create, array());

        // User 2 creates experiment C and adds a link to experiment A
        $Experiments = new Experiments(new Users(2, 1));
        $ExperimentCId = $Experiments->create(
            title: 'Experiment C',
            canread: BasePermissions::Team->toJson(),
        );
        $Experiments->setId($ExperimentCId);
        $Experiments->ExperimentsLinks->setId($ExperimentAId);
        $Experiments->ExperimentsLinks->postAction(Action::Create, array());

        // User 2 imports the links from experiment A to experiment C
        $Experiments->ExperimentsLinks->postAction(Action::Duplicate, array());

        // User 2 should only see the link to experiment A but not the imported link to experiment B
        $titles = array_column($Experiments->ExperimentsLinks->readAll(), 'title');
        $this->assertEquals(1, count($titles));
        $this->assertNotContains($secretTitle, $titles);

        // User 1 should see both links (i.e to experiments A and B) in experiment C
        $Experiments = new Experiments(new Users(1, 1), $ExperimentCId);
        $titles = array_column($Experiments->ExperimentsLinks->readAll(), 'title');
        $this->assertEquals(2, count($titles));
        $this->assertContains($secretTitle, $titles);
        $this->assertContains('Experiment A', $titles);
    }

    public function testObeyReadPermissionAfterLinksImportAcrossTeams(): void
    {
        // But perhaps more importantly, we need to make sure that the permissions are obeyed when importing links across teams

        // User 1 from team alpha creates experiment A that is visible to the organization
        $Experiments = new Experiments(new Users(1, 1));
        $ExperimentAId = $Experiments->create(
            title: 'Experiment A',
            canread: BasePermissions::Organization->toJson(),
        );

        // User 1 creates experiment B that is visible to their team
        $secretTitle = 'Experiment B - This title shall not be visible to user 5 after importing links';
        $ExperimentBId = $Experiments->create(
            title: $secretTitle,
            canread: BasePermissions::Team->toJson(),
        );

        // Experiment A links to experiment B
        $Experiments->setId($ExperimentAId);
        $Experiments->ExperimentsLinks->setId($ExperimentBId);
        $Experiments->ExperimentsLinks->postAction(Action::Create, array());

        // User 5 from team bravo creates experiment C and adds a link to experiment A
        $Experiments = new Experiments(new Users(5, 2));
        $ExperimentCId = $Experiments->create(
            title: 'Experiment C',
            canread: BasePermissions::Organization->toJson(),
        );
        $Experiments->setId($ExperimentCId);
        $Experiments->ExperimentsLinks->setId($ExperimentAId);
        $Experiments->ExperimentsLinks->postAction(Action::Create, array());

        // User 2 imports the links from experiment A to experiment C
        $Experiments->ExperimentsLinks->postAction(Action::Duplicate, array());

        // User 2 should only see the link to experiment A but not the imported link to experiment B
        $titles = array_column($Experiments->ExperimentsLinks->readAll(), 'title');
        $this->assertEquals(1, count($titles));
        $this->assertNotContains($secretTitle, $titles);

        // User 1 should see both links (i.e to experiments A and B) in experiment C
        // Need AuthenticatedUser to read across teams (see Permissions::getCan)
        $Experiments = new Experiments(new AuthenticatedUser(1, 1), $ExperimentCId);
        $titles = array_column($Experiments->ExperimentsLinks->readAll(), 'title');
        $this->assertEquals(2, count($titles));
        $this->assertContains($secretTitle, $titles);
        $this->assertContains('Experiment A', $titles);
    }
}
