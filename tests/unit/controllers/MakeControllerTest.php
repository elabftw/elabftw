<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Controllers;

use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Models\Users\AnonymousUser;
use Symfony\Component\HttpFoundation\Request;

class MakeControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testAnonymousUserCannotMakeSchedulerReport(): void
    {
        $Controller = new MakeController(new AnonymousUser(1), Request::create('/make.php?format=schedulerReport'));
        $this->expectException(ForbiddenException::class);
        $Controller->getResponse();
    }
}
