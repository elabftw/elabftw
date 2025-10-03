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
use Elabftw\Traits\TestsUtilsTrait;
use ReflectionClass;

class AbstractHtmlControllerTest extends \PHPUnit\Framework\TestCase
{
    use ControllerUtilsTrait;
    use TestsUtilsTrait;

    public function testGetResponse(): void
    {
        $ref = new ReflectionClass(AbstractHtmlController::class);
        $this->assertTrue($ref->getMethod('getPageTitle')->isAbstract());
        $this->assertTrue($ref->getMethod('getTemplate')->isAbstract());
        $this->assertTrue($ref->getMethod('getData')->hasReturnType());
        $this->assertSame('array', $ref->getMethod('getData')->getReturnType());
    }
}
