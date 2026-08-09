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

use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;

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
        $this->assertTrue($DeviceTokenValidator->validate());
    }

    public function testValidateValidTokenWrongUser(): void
    {
        $validToken = DeviceToken::getToken(1);
        $TokenAttacker = new DeviceTokenValidator($this->config, $validToken, 80);
        $this->assertFalse($TokenAttacker->validate());
    }

    public function testExpiredTokenIsRejected(): void
    {
        $token = $this->config->builder()
            ->permittedFor('brute-force-protection')
            ->issuedAt(new DateTimeImmutable('-2 hours'))
            ->expiresAt(new DateTimeImmutable('-1 hour'))
            ->withClaim('userid', 1)
            ->getToken(
                $this->config->signer(),
                $this->config->signingKey(),
            )
            ->toString();

        $validator = new DeviceTokenValidator($this->config, $token, 1);

        self::assertFalse($validator->validate());
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

    public function testValidateReturnFalseWhenParsedTokenIsNotUnencrypted(): void
    {
        $parsedToken = $this->createStub(Token::class);
        $parser = $this->createStub(Parser::class);
        $parser->method('parse')->willReturn($parsedToken);

        $config = $this->config->withParser($parser);

        $DeviceTokenValidator = new DeviceTokenValidator($config, 'not-an-unencrypted-token', 1);
        $this->assertFalse($DeviceTokenValidator->validate());
    }
}
