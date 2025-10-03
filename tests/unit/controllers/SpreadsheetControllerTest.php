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

use ReflectionClass;

class SpreadsheetControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testControllerReturnsTemplate(): void
    {
        $ref = new ReflectionClass(SpreadsheetController::class);

        $controller = $ref->newInstanceWithoutConstructor();
        // get the protected method
        $getTemplate = $ref->getMethod('getTemplate');
        $getTemplate->setAccessible(true);
        $result = $getTemplate->invoke($controller);
        $this->assertSame('spreadsheet-iframe.html', $result);

        $getPageTitle = $ref->getMethod('getPageTitle');
        $getPageTitle->setAccessible(true);
        $result = $getPageTitle->invoke($controller);
        $this->assertSame('Spreadsheet Editor', $result);
    }
}
