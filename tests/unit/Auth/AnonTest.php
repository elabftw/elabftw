<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Auth;

use Elabftw\Elabftw\AuthResponse;
use Elabftw\Exceptions\IllegalActionException;

class AnonTest extends \PHPUnit\Framework\TestCase
{
    public function testTryAuth(): void
    {
        $authResponse = new Anon(true, 1)->tryAuth();
        $this->assertInstanceOf(AuthResponse::class, $authResponse);
        $this->assertTrue($authResponse->isAnonymous);

        // now try anon login but it's disabled by sysadmin
        $this->expectException(IllegalActionException::class);
        new Anon(false, 1);
    }
}
