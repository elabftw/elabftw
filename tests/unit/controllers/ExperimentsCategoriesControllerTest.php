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

use Elabftw\Traits\ControllerUtilsTrait;

class ExperimentsCategoriesControllerTest extends \PHPUnit\Framework\TestCase
{
    use ControllerUtilsTrait;

    public function testGetTemplate(): void
    {
        $this->assertSame(
            'experiments-categories.html',
            $this->invokeProtected($this->makeWithoutConstructor(ExperimentsCategoriesController::class), 'getTemplate')
        );
    }

    public function testGetPageTitle(): void
    {
        $this->assertSame(
            'Experiments categories',
            $this->invokeProtected($this->makeWithoutConstructor(ExperimentsCategoriesController::class), 'getPageTitle')
        );
    }
}
