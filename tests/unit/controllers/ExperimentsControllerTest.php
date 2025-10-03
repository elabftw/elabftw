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

use Elabftw\Models\AbstractEntity;
use Elabftw\Traits\ControllerUtilsTrait;
use Elabftw\Traits\TestsUtilsTrait;
use ReflectionClass;

class ExperimentsControllerTest extends \PHPUnit\Framework\TestCase
{
    use ControllerUtilsTrait;
    use TestsUtilsTrait;

    public function testGetPageTitleWhenEntityIsExperiments(): void
    {
        $controller = $this->makeWithoutConstructor(ExperimentsController::class);
        $Experiment = $this->getFreshExperiment();
        $this->initEntity($controller, $Experiment, 'Entity');
        $this->assertSame('Experiments', $this->invokeProtected($controller, 'getPageTitle'));
    }

    public function testGetPageTitleWhenEntityIsExperimentsTemplates(): void
    {
        $controller = $this->makeWithoutConstructor(ExperimentsController::class);
        $Template = $this->getFreshTemplate();
        $this->initEntity($controller, $Template, 'Entity');
        $this->assertSame('Experiment templates', $this->invokeProtected($controller, 'getPageTitle'));
    }

    protected function initEntity(object $controller, AbstractEntity $entity, string $property): void
    {
        $rp = new ReflectionClass($controller)->getProperty($property);
        $rp->setAccessible(true);
        $rp->setValue($controller, $entity);
    }
}
