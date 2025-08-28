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
use Elabftw\Models\Links\Experiments2ItemsLinks;
use Elabftw\Models\Users\AuthenticatedUser;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;

class LinksTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Experiments $Experiments;

    private Items $Items;

    protected function setUp(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $this->Experiments = $this->getFreshExperimentWithGivenUser($user);
        $this->Items = $this->getFreshItemWithGivenUser($user);
        $this->Experiments->ExperimentsLinks->setId($this->Experiments->id);
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals(sprintf('api/v2/experiments/%d/experiments2experiments/', $this->Experiments->id), $this->Experiments->ExperimentsLinks->getApiPath());
    }

    public function testCreateReadDestroy(): void
    {
        $this->Experiments->ItemsLinks->setId($this->Items->id);
        $this->Experiments->ItemsLinks->postAction(Action::Create, array());
        $count = count($this->Experiments->ItemsLinks->readAll());
        $this->assertEquals(1, $count);
        $this->Experiments->ItemsLinks->destroy();
        $this->assertEquals(0, count($this->Experiments->ItemsLinks->readAll()));

        $this->Experiments->ExperimentsLinks->setId($this->Experiments->id - 1);
        $this->Experiments->ExperimentsLinks->postAction(Action::Create, array());
        $count = count($this->Experiments->ExperimentsLinks->readAll());
        $this->assertEquals(1, $count);
        $this->Experiments->ExperimentsLinks->destroy();
        $this->assertEquals(0, count($this->Experiments->ExperimentsLinks->readAll()));
    }

    public function testImport(): void
    {
        // create a link in a db item
        $Items1 = $this->getFreshItem();
        $Items2 = $this->getFreshItem();
        $Items1->ItemsLinks->setId($Items2->id);
        $Items1->ItemsLinks->postAction(Action::Create, array());
        // now import this in our experiment like if we click the import links button
        $Links = new Experiments2ItemsLinks($this->Experiments, $Items1->id);
        $this->assertIsInt($Links->postAction(Action::Duplicate, array()));
        $this->Experiments->ItemsLinks->setId($Items1->id);
        $this->assertIsInt($this->Experiments->ItemsLinks->postAction(Action::Duplicate, array()));
        $this->Experiments->ExperimentsLinks->setId($this->Experiments->id);
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
        $Templates = new Templates($this->getRandomUserInTeam(1));
        // create a template
        $id = $Templates->create(title: 'some template');
        $Templates->setId($id);
        // add an item link
        $Item = $this->getFreshItemWithGivenUser($Templates->Users);
        $Templates->ItemsLinks->setId($Item->id);
        $Templates->ItemsLinks->postAction(Action::Create, array());
        // now create an experiment from that template
        $expid = $this->Experiments->postAction(Action::Create, array('template' => $id));
        $this->Experiments->setId($expid);
        $this->assertEquals(1, count($this->Experiments->ItemsLinks->readAll()));
    }

    public function testReadExperimentsLinksFromTemplate(): void
    {
        $Templates = new Templates($this->getRandomUserInTeam(1));
        $tplid = $Templates->create();
        $Templates->setId($tplid);
        $this->assertEmpty($Templates->ExperimentsLinks->readAll());
        $Templates->ExperimentsLinks->setId($this->Experiments->id);
        $Templates->ExperimentsLinks->postAction(Action::Create, array());
        $this->assertEquals(1, count($Templates->ExperimentsLinks->readAll()));
    }

    public function testObeyReadPermissionAfterLinksImportWithinTeam(): void
    {
        // compare to #5523, #5524
        // In the most simple case, we stay within a given team

        // User 1 creates experiment A that is visible to the team
        $Experiments = $this->getFreshExperiment();
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
        $Experiments = $this->getFreshExperiment();
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

    public function testCreateIgnoresSelfLinking(): void
    {
        $titles = array_column($this->Experiments->ExperimentsLinks->readAll(), 'title');
        $this->Experiments->ExperimentsLinks->setId($this->Experiments->id);
        $result = $this->Experiments->ExperimentsLinks->postAction(Action::Create, array());
        $this->assertSame(0, $result);
        $titlesAfter = array_column($this->Experiments->ExperimentsLinks->readAll(), 'title');
        $this->assertSame(count($titles), count($titlesAfter));
    }
}
