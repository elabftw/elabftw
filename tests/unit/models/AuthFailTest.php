<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Services\DeviceToken;

class AuthFailTest extends \PHPUnit\Framework\TestCase
{
    public function testRegisterWithoutDeviceToken(): void
    {
        $AuthFail = new AuthFail(10, 1);
        $AuthFail->register();
    }

    public function testRegisterWithDeviceToken(): void
    {
        $DeviceToken = new DeviceToken();
        $deviceToken = $DeviceToken->getToken(1);
        $AuthFail = new AuthFail(10, 1, $deviceToken);
        $AuthFail->register();
    }

    public function testLockDevice(): void
    {
        $DeviceToken = new DeviceToken();
        $deviceToken = $DeviceToken->getToken(1);
        $AuthFail = new AuthFail(0, 1, $deviceToken);
        $AuthFail->register();
    }

    public function testLockUser(): void
    {
        $AuthFail = new AuthFail(0, 1);
        $AuthFail->register();
    }
}
