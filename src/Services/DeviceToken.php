<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use DateTimeImmutable;
use Defuse\Crypto\Key;
use Elabftw\Models\Config;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

/**
 * DeviceToken generator
 */
final class DeviceToken
{
    public static function getToken(int $userid): string
    {
        $now = new DateTimeImmutable();
        $config = self::getConfig();
        $token = $config->builder()
                // Configures the issuer (iss claim)
                //->issuedBy('https://elab.local:3148')
                // Configures the audience (aud claim)
                ->permittedFor('brute-force-protection')
                // Configures the id (jti claim)
                //->identifiedBy('4f1g23a12aa')
                // Configures the time that the token was issue (iat claim)
                ->issuedAt($now)
                // Configures the time that the token can be used (nbf claim)
                //->canOnlyBeUsedAfter($now->modify('+1 minute'))
                // Configures the expiration time of the token (exp claim)
                // @psalm-suppress PossiblyFalseArgument
                ->expiresAt($now->modify('+3 months'))
                // Configures a new claim, called "uid"
                ->withClaim('userid', $userid)
                // Builds a new token
                ->getToken($config->signer(), $config->signingKey());
        return $token->toString();
    }

    public static function getConfig(): Configuration
    {
        $secretKey = Key::loadFromAsciiSafeString(Config::fromEnv('SECRET_KEY'));
        /** @psalm-suppress ArgumentTypeCoercion */
        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($secretKey->getRawBytes()), // @phpstan-ignore-line
        );
        // TODO validate the userid claim and other stuff
        $config->setValidationConstraints(new PermittedFor('brute-force-protection'));
        $config->setValidationConstraints(new SignedWith($config->signer(), $config->signingKey()));
        return $config;
    }
}
