<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\AuthResponse;
use Elabftw\Exceptions\IllegalActionException;

class AnonAuthTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->configArr = array(
            'anon_users' => '1',
        );
        $this->AnonAuth = new AnonAuth(
            $this->configArr,
            1,
        );
    }

    public function testTryAuth()
    {
        $authResponse = $this->AnonAuth->tryAuth();
        $this->assertInstanceOf(AuthResponse::class, $authResponse);
        $this->assertEquals('anon', $authResponse->isAuthBy);
        $this->assertTrue($authResponse->isAnonymous);

        // now try anon login but it's disabled by sysadmin
        $this->expectException(IllegalActionException::class);
        $AnonAuth = new AnonAuth(
            array('anon_users' => '0'),
            1,
        );
    }
}
