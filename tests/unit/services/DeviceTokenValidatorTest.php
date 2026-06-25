<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Lcobucci\JWT\Configuration;

class DeviceTokenValidatorTest extends \PHPUnit\Framework\TestCase
{
    private Configuration $config;

    protected function setUp(): void
    {
        $this->config = DeviceToken::getConfig();
    }

    public function testValidateValidToken(): void
    {
        $validToken = DeviceToken::getToken(1);
        $DeviceTokenValidator = new DeviceTokenValidator($this->config, $validToken, 1);
        $DeviceTokenValidator->validate();
    }

    public function testValidateValidTokenWrongUser(): void
    {
        $validToken = DeviceToken::getToken(1);
        $TokenAttacker = new DeviceTokenValidator($this->config, $validToken, 80);
        $TokenAttacker->validate();
    }

    public function testUndecodableToken(): void
    {
        $DeviceTokenValidator = new DeviceTokenValidator($this->config, '..', 1);
        $this->assertFalse($DeviceTokenValidator->validate());
    }

    public function testNotParsableToken(): void
    {
        $DeviceTokenValidator = new DeviceTokenValidator($this->config, 'this cannot be parsed!', 1);
        $this->assertFalse($DeviceTokenValidator->validate());
    }
}
