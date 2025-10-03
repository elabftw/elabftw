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
}
