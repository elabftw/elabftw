<?php

declare(strict_types=1);

namespace Elabftw\Controllers;

use Elabftw\Elabftw\App;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Links\Containers2ItemsLinks;
use Elabftw\Models\StorageUnits;
use Elabftw\Traits\TestsUtilsTrait;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

use function array_map;
use function sprintf;

class AbstractEntityControllerTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    public function testShowProvidesVisibleTeamsToSelectedPermissionsModals(): void
    {
        $user = $this->getRandomUserInTeam(1);
        // create the application using an experiments-page request
        $App = new App(
            Request::create('/experiments.php'),
            new Session(new MockArraySessionStorage()),
            Config::getConfig(),
            App::getDefaultLogger(),
            $user,
        );

        // init the application services and the current team
        $App->boot();

        // create the experiments controller and render its show page
        $response = new ExperimentsController($App, new Experiments($user))->show();
        // parse the rendered HTML so elements can be queried with CSS selectors
        $Crawler = new Crawler((string) $response->getContent());
        // convert all visible team IDs into the values expected in the modal options
        $expected = array_map(
            // format like twig renders it
            static fn(array $team): string => sprintf('team:%d', $team['id']),
            // retrieve every team visible to the current user
            $App->Teams->readAllVisible(),
        );

        // check both the read-permission and write-permission modals
        foreach (array('canread', 'canwrite') as $permission) {
            // find every team option inside the current permission modal.
            $actual = $Crawler
                ->filter(sprintf('#%sSelected_select_teams option', $permission))
                // extract the value attribute from every matching option.
                ->each(static fn(Crawler $option): string => (string) $option->attr('value'));
            // confirm that the modal contains exactly all visible teams.
            self::assertSame($expected, $actual);
        }
    }

    public function testShowRendersTheBatchContainerModalWithFreeSlotCounts(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $StorageUnits = new StorageUnits($user, false);
        $storageId = $StorageUnits->create('Box the batch modal must count correctly', capacity: 9);
        // occupy some of it, so a count equal to the capacity would not pass by accident
        $Item = $this->getFreshItem();
        new Containers2ItemsLinks($Item, $storageId)->createWithQuantity(1.0, 'mL');
        new Containers2ItemsLinks($Item, $storageId)->createWithQuantity(1.0, 'mL');

        $App = new App(
            Request::create('/database.php'),
            new Session(new MockArraySessionStorage()),
            Config::getConfig(),
            App::getDefaultLogger(),
            $user,
        );
        $App->boot();

        $response = new DatabaseController($App, new Items($user))->show();
        $Crawler = new Crawler((string) $response->getContent());

        // the show page has no storage section of its own, so the tree only reaches it
        // through the batch modal; without it the new button would open an empty dialog
        self::assertCount(1, $Crawler->filter('#storageModal[data-with-selected] [data-storage-tree]'));

        // the js divides this number by the size of the selection to get each ceiling, so a
        // count that disagrees with the guard would block or overbook whole batches
        $stepper = $Crawler->filter(sprintf('input[data-action="container-qty-input"][data-storage-id="%d"]', $storageId));
        self::assertCount(1, $stepper);
        // two of the nine slots are taken, so a stepper that echoed the capacity fails here
        self::assertSame(2, $StorageUnits->countContainers($storageId));
        self::assertSame('7', $stepper->attr('data-slots-left'));
        self::assertSame('7', $stepper->attr('max'));
    }
}
