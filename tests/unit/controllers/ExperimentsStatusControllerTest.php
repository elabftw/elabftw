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
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\ExperimentsStatus;
use Elabftw\Models\Teams;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\ControllerUtilsTrait;
use Elabftw\Traits\TestsUtilsTrait;
use ReflectionClass;

class ExperimentsStatusControllerTest extends \PHPUnit\Framework\TestCase
{
    use ControllerUtilsTrait;
    use TestsUtilsTrait;

    public function testGetTemplate(): void
    {
        $this->assertSame(
            'experiments-status.html',
            $this->invokeProtected($this->makeWithoutConstructor(ExperimentsStatusController::class), 'getTemplate')
        );
    }

    public function testGetPageTitle(): void
    {
        $this->assertSame(
            'Experiments status',
            $this->invokeProtected($this->makeWithoutConstructor(ExperimentsStatusController::class), 'getPageTitle')
        );
    }

    public function testGetModelReturnsExperimentsStatus(): void
    {
        $controller = $this->makeWithoutConstructor(ExperimentsStatusController::class);
        // build a minimal $app with a Teams instance. Prevents $app must not be accessed before initialization
        $app = new ReflectionClass(App::class)->newInstanceWithoutConstructor();
        $teams = new Teams(new Users(1, 1), 1);
        // inject Teams into $app
        $this->injectInto($app, $teams, 'Teams');
        // inject $app into controller
        $this->injectInto($controller, $app, 'app');

        $model = $this->invokeProtected($controller, 'getModel');
        $this->assertInstanceOf(ExperimentsStatus::class, $model);
    }

    protected function injectInto(object $controller, object $entity, string $property): void
    {
        $rp = new ReflectionClass($controller)->getProperty($property);
        $rp->setAccessible(true);
        $rp->setValue($controller, $entity);
    }
}
