<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Auth\DeviceToken;

class AuthFailTest extends \PHPUnit\Framework\TestCase
{
    public function testRegisterWithoutDeviceToken(): void
    {
        $AuthFail = new AuthFail(10, 1);
        $this->assertFalse($AuthFail->register());
    }

    public function testGetLockedUsersCount(): void
    {
        $this->assertIsInt((new AuthFail())->getLockedUsersCount());
    }

    public function testRegisterWithDeviceToken(): void
    {
        $DeviceToken = new DeviceToken();
        $deviceToken = $DeviceToken->getToken(1);
        $AuthFail = new AuthFail(10, 1, $deviceToken);
        $this->assertFalse($AuthFail->register());
    }

    public function testInvalidDeviceTokenFallsBackToUserLockout(): void
    {
        $AuthFail = new AuthFail(0, 1, 'attacker-controlled');
        $lockedDevices = $AuthFail->getLockoutDevicesCount();

        $this->assertTrue($AuthFail->register());
        $this->assertSame(
            $lockedDevices,
            $AuthFail->getLockoutDevicesCount(),
        );
    }

    public function testDeviceTokenForAnotherUserFallsBackToUserLockout(): void
    {
        $deviceToken = DeviceToken::getToken(2);
        $AuthFail = new AuthFail(0, 1, $deviceToken);
        $lockedDevices = $AuthFail->getLockoutDevicesCount();

        $this->assertTrue($AuthFail->register());
        $this->assertSame(
            $lockedDevices,
            $AuthFail->getLockoutDevicesCount(),
        );
    }

    public function testLockDevice(): void
    {
        $DeviceToken = new DeviceToken();
        $deviceToken = $DeviceToken->getToken(1);
        $AuthFail = new AuthFail(0, 1, $deviceToken);
        $this->assertTrue($AuthFail->register());
    }

    public function testLockUser(): void
    {
        $AuthFail = new AuthFail(0, 1);
        $this->assertTrue($AuthFail->register());
    }
}
