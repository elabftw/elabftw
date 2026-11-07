<?php

declare(strict_types=1);

namespace Elabftw\Controllers;

use Elabftw\Elabftw\App;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
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

    public function testShowRendersTheBatchContainerModalWithoutReadingStorage(): void
    {
        $user = $this->getRandomUserInTeam(1);
        // a location exists, so a tree rendered server side would show up below
        $storageId = new StorageUnits($user, false)->create('Box the show page must not render', capacity: 9);

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

        // the modal and its empty tree are there for the js to fill when the modal opens
        self::assertCount(1, $Crawler->filter('#storageModal[data-with-selected] [data-storage-tree-lazy] [data-storage-tree-content]'));
        self::assertCount(1, $Crawler->filter('[data-action="toggle-modal"][data-target="storageModal"]'));
        // but the hierarchy query is not made on page load: no location reaches the html
        self::assertCount(0, $Crawler->filter(sprintf('[data-storage-id="%d"]', $storageId)));
        self::assertCount(0, $Crawler->filter('input[data-action="container-qty-input"]'));
    }
}
