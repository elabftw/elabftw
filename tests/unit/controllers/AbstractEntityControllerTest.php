<?php

declare(strict_types=1);

namespace Elabftw\Controllers;

use Elabftw\Elabftw\App;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
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
}
