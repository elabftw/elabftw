<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Exceptions\InvalidDeviceTokenException;

class DeviceTokenTest extends \PHPUnit\Framework\TestCase
{
    public function testValidateValidToken(): void
    {
        $validToken = (new DeviceToken())->getToken(1);
        $DeviceToken = new DeviceToken($validToken);
        $DeviceToken->validate();
    }

    public function testUndecodableToken(): void
    {
        $DeviceToken = new DeviceToken('..');
        $this->expectException(InvalidDeviceTokenException::class);
        $DeviceToken->validate();
    }

    public function testNotParsableToken(): void
    {
        $DeviceToken = new DeviceToken('this cannot be parsed!');
        $this->expectException(InvalidDeviceTokenException::class);
        $DeviceToken->validate();
    }
}
