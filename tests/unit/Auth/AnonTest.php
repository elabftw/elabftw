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
    private array $configArr;

    private Anon $AnonAuth;

    protected function setUp(): void
    {
        $this->configArr = array(
            'anon_users' => '1',
        );
        $this->AnonAuth = new Anon(
            $this->configArr,
            1,
        );
    }

    public function testTryAuth(): void
    {
        $authResponse = $this->AnonAuth->tryAuth();
        $this->assertInstanceOf(AuthResponse::class, $authResponse);
        $this->assertTrue($authResponse->isAnonymous);

        // now try anon login but it's disabled by sysadmin
        $this->expectException(IllegalActionException::class);
        new Anon(
            array('anon_users' => '0'),
            1,
        );
    }
}
