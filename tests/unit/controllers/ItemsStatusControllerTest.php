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
use Elabftw\Models\ItemsStatus;
use Elabftw\Models\Teams;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\ControllerUtilsTrait;
use Elabftw\Traits\TestsUtilsTrait;
use ReflectionClass;

class ItemsStatusControllerTest extends \PHPUnit\Framework\TestCase
{
    use ControllerUtilsTrait;
    use TestsUtilsTrait;

    public function testGetTemplate(): void
    {
        $this->assertSame(
            'resources-status.html',
            $this->invokeProtected($this->makeWithoutConstructor(ItemsStatusController::class), 'getTemplate')
        );
    }

    public function testGetPageTitle(): void
    {
        $this->assertSame(
            'Resources status',
            $this->invokeProtected($this->makeWithoutConstructor(ItemsStatusController::class), 'getPageTitle')
        );
    }

    public function testGetModelReturnsItemsStatus(): void
    {
        $controller = $this->makeWithoutConstructor(ItemsStatusController::class);
        // build a minimal $app with a Teams instance. Prevents $app must not be accessed before initialization
        $app = new ReflectionClass(App::class)->newInstanceWithoutConstructor();
        $teams = new Teams(new Users(1, 1), 1);
        // inject Teams into $app
        $this->injectInto($app, $teams, 'Teams');
        // inject $app into controller
        $this->injectInto($controller, $app, 'app');

        $model = $this->invokeProtected($controller, 'getModel');
        $this->assertInstanceOf(ItemsStatus::class, $model);
    }
}
