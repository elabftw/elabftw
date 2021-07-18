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
use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\InvalidDeviceTokenException;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;

/**
 * Everything related to the JWT for device identification and brute force attacks protection
 */
class DeviceToken
{
    private Db $Db;

    private Configuration $config;

    public function __construct(private ?string $deviceToken = null)
    {
        $this->config = $this->getConfig();
        $this->Db = Db::getConnection();
    }

    public function validate(): void
    {
        try {
            $parsedToken = $this->config->parser()->parse($this->deviceToken);
            $this->config->validator()->assert($parsedToken, ...$this->config->validationConstraints());
            // also check if the device token is not in the locklist
            $sql = 'SELECT COUNT(id) FROM lockout_devices WHERE device_token = :device_token AND locked_at > (NOW() - INTERVAL 1 HOUR)';
            $req = $this->Db->prepare($sql);
            $req->bindParam(':device_token', $this->deviceToken);
            $req->execute();
            if ($req->fetchColumn() > 0) {
                throw new IllegalActionException('Invalid device token.');
            }
            // group all the possible exceptions into one because we don't really care the reason why the token might be invalid
        } catch (CannotDecodeContent | InvalidTokenStructure | RequiredConstraintsViolated | IllegalActionException $e) {
            throw new InvalidDeviceTokenException();
        }
    }

    public function getToken(int $userid): string
    {
        $now = new DateTimeImmutable();
        $token = $this->config->builder()
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
                ->expiresAt($now->modify('+3 months'))
                // Configures a new claim, called "uid"
                ->withClaim('userid', $userid)
                // Builds a new token
                ->getToken($this->config->signer(), $this->config->signingKey());
        return $token->toString();
    }

    private function getConfig(): Configuration
    {
        $secretKey = Key::loadFromAsciiSafeString(\SECRET_KEY);
        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($secretKey->getRawBytes()),
        );
        // TODO validate the userid claim and other stuff
        $config->setValidationConstraints(new PermittedFor('brute-force-protection'));
        $config->setValidationConstraints(new SignedWith($config->signer(), $config->signingKey()));
        return $config;
    }
}
