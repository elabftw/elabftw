<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Controllers;

use Elabftw\Elabftw\App;
use Elabftw\Models\Teams;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\ControllerUtilsTrait;
use ReflectionClass;

class DashboardControllerTest extends \PHPUnit\Framework\TestCase
{
    use ControllerUtilsTrait;

    public function testGetTemplate(): void
    {
        $this->assertSame(
            'dashboard.html',
            $this->invokeProtected($this->makeWithoutConstructor(DashboardController::class), 'getTemplate')
        );
    }

    public function testGetPageTitle(): void
    {
        $this->assertSame(
            'Dashboard',
            $this->invokeProtected($this->makeWithoutConstructor(DashboardController::class), 'getPageTitle')
        );
    }

    public function testGetData(): void
    {
        $controller = $this->makeWithoutConstructor(DashboardController::class);
        $app = new ReflectionClass(App::class)->newInstanceWithoutConstructor();
        $users = new Users(1, 1);
        $teams = new Teams($users, 1);
        $this->injectInto($app, $teams, 'Teams');
        $this->injectInto($app, $users, 'Users');
        $this->injectInto($controller, $app, 'app');
        $data = $this->invokeProtected($controller, 'getData');

        $this->assertIsArray($data);
        $this->assertArrayHasKey('pageTitle', $data);
        $this->assertArrayHasKey('bookingsArr', $data);
        $this->assertArrayHasKey('itemsStatusArr', $data);
        $this->assertArrayHasKey('experimentsStatusArr', $data);
        $this->assertArrayHasKey('itemsArr', $data);
        $this->assertArrayHasKey('templatesArr', $data);
        $this->assertArrayHasKey('usersArr', $data);
        $this->assertArrayHasKey('visibilityArr', $data);
        $this->assertIsArray($data['bookingsArr']);
        $this->assertIsArray($data['itemsTemplatesArr']);
    }
}
