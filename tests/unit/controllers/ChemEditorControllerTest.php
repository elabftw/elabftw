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
use Elabftw\Models\Users\Users;
use Elabftw\Traits\ControllerUtilsTrait;
use ReflectionClass;

class ChemEditorControllerTest extends \PHPUnit\Framework\TestCase
{
    use ControllerUtilsTrait;

    public function testGetTemplate(): void
    {
        $this->assertSame(
            'chem-editor.html',
            $this->invokeProtected($this->makeWithoutConstructor(ChemEditorController::class), 'getTemplate')
        );
    }

    public function testGetPageTitle(): void
    {
        $this->assertSame(
            'Chemical Structure Editor',
            $this->invokeProtected($this->makeWithoutConstructor(ChemEditorController::class), 'getPageTitle')
        );
    }

    public function testGetData(): void
    {
        $controller = $this->makeWithoutConstructor(ChemEditorController::class);
        $app = new ReflectionClass(App::class)->newInstanceWithoutConstructor();
        $users = new Users(1, 1);

        $this->injectInto($app, $users, 'Users');
        $this->injectInto($controller, $app, 'app');
        $data = $this->invokeProtected($controller, 'getData');

        $this->assertIsArray($data);
        $this->assertArrayHasKey('pageTitle', $data);
        $this->assertArrayHasKey('resourceCategoriesArr', $data);
        $this->assertIsArray($data['resourceCategoriesArr']);
    }
}
