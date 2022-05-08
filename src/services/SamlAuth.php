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
use Elabftw\Elabftw\AuthResponse;
use Elabftw\Elabftw\Saml;
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Models\Config;
use Elabftw\Models\ExistingUser;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;
use Elabftw\Models\ValidatedUser;
use function is_array;
use function is_int;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use OneLogin\Saml2\Auth as SamlAuthLib;
use const SECRET_KEY;

/**
 * SAML auth service
 */
class SamlAuth implements AuthInterface
{
    private const TEAM_SELECTION_REQUIRED = 1;

    private AuthResponse $AuthResponse;

    private array $samlUserdata = array();

    private ?string $samlSessionIdx;

    public function __construct(private SamlAuthLib $SamlAuthLib, private array $configArr, private array $settings)
    {
        $this->AuthResponse = new AuthResponse();
    }

    public static function getJWTConfig(): Configuration
    {
        $secretKey = Key::loadFromAsciiSafeString(SECRET_KEY);
        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($secretKey->getRawBytes()),
        );
        // TODO validate the userid claim and other stuff
        $config->setValidationConstraints(new PermittedFor('saml-session'));
        $config->setValidationConstraints(new SignedWith($config->signer(), $config->signingKey()));
        return $config;
    }

    public function encodeToken(int $idpId): string
    {
        $now = new DateTimeImmutable();
        $config = self::getJWTConfig();
        $token = $config->builder()
                // Configures the audience (aud claim)
                ->permittedFor('saml-session')
                // Configures the time that the token was issue (iat claim)
                ->issuedAt($now)
                // Configures the expiration time of the token (exp claim)
                ->expiresAt($now->modify('+1 months'))
                // Configures a new claim, called "uid"
                ->withClaim('sid', $this->getSessionIndex())
                ->withClaim('idp_id', $idpId)
                // Builds a new token
                ->getToken($config->signer(), $config->signingKey());
        return $token->toString();
    }

    public static function decodeToken(string $token): array
    {
        $conf = self::getJWTConfig();

        try {
            $parsedToken = $conf->parser()->parse($token);
            if (!$parsedToken instanceof UnencryptedToken) {
                throw new UnauthorizedException('Decoding JWT Token failed');
            }
            $conf->validator()->assert($parsedToken, ...$conf->validationConstraints());

            return array($parsedToken->claims()->get('sid'), $parsedToken->claims()->get('idp_id'));
        } catch (CannotDecodeContent | InvalidTokenStructure | RequiredConstraintsViolated $e) {
            throw new UnauthorizedException('Decoding JWT Token failed');
        }
    }

    public function tryAuth(): AuthResponse
    {
        $returnUrl = $this->settings['baseurl'] . '/index.php?acs';
        $this->SamlAuthLib->login($returnUrl);
        return $this->AuthResponse;
    }

    public function assertIdpResponse(): AuthResponse
    {
        $this->SamlAuthLib->processResponse();
        $errors = $this->SamlAuthLib->getErrors();

        // Display the errors if we are in debug mode
        if (!empty($errors)) {
            $error = Tools::error();
            // get more verbose if debug mode is active
            if ($this->configArr['debug']) {
                $error = implode(', ', $errors);
            }
            throw new UnauthorizedException($error);
        }

        if (!$this->SamlAuthLib->isAuthenticated()) {
            throw new UnauthorizedException('Authentication with IDP failed!');
        }

        // get the user information sent by IDP
        $this->samlUserdata = $this->SamlAuthLib->getAttributes();

        // get session index
        $this->samlSessionIdx = $this->SamlAuthLib->getSessionIndex();

        // GET EMAIL
        $email = $this->getEmail();

        // GET POPULATED USERS OBJECT
        $Users = $this->getUsers($email);
        if (!$Users instanceof Users) {
            $this->AuthResponse->userid = 0;
            $this->AuthResponse->initTeamRequired = true;
            $this->AuthResponse->initTeamUserInfo = array(
                'email' => $email,
                'firstname' => $this->getName(),
                'lastname' => $this->getName(true),
            );
            return $this->AuthResponse;
        }

        $userid = (int) $Users->userData['userid'];

        $this->AuthResponse->userid = $userid;
        $this->AuthResponse->mfaSecret = $Users->userData['mfa_secret'];

        // synchronize the teams from the IDP
        // because teams can change since the time the user was created
        if ($this->configArr['saml_sync_teams']) {
            $Teams = new Teams($Users);
            $Teams->synchronize($userid, $this->getTeamsFromIdpResponse());
        }

        // load the teams from db
        $this->AuthResponse->setTeams();

        return $this->AuthResponse;
    }

    public function getSessionIndex(): ?string
    {
        return $this->samlSessionIdx;
    }

    private function getEmail(): string
    {
        $email = $this->samlUserdata[$this->settings['idp']['emailAttr']];

        if (is_array($email)) {
            $email = $email[0];
        }

        if ($email === null) {
            throw new ImproperActionException('Could not find email in response from IDP! Aborting.');
        }
        return $email;
    }

    /**
     * Get firstname or lastname from idp
     **/
    private function getName(bool $last = false): string
    {
        // toggle firstname or lastname
        $selector = $last ? 'lnameAttr' : 'fnameAttr';

        $name = $this->samlUserdata[$this->settings['idp'][$selector] ?? 'Unknown'] ?? 'Unknown';
        if (is_array($name)) {
            return $name[0];
        }
        return $name;
    }

    private function getTeamsFromIdpResponse(): array
    {
        if (empty($this->settings['idp']['teamAttr'])) {
            throw new ImproperActionException('Cannot synchronize team(s) from IDP if no value is set for looking up team(s) in IDP response!');
        }
        $teams = $this->samlUserdata[$this->settings['idp']['teamAttr']];
        if (empty($teams)) {
            throw new ImproperActionException('Could not find team(s) in IDP response!');
        }

        $Teams = new Teams(new Users());
        if (is_array($teams)) {
            return $Teams->getTeamsFromIdOrNameOrOrgidArray($teams);
        }

        if (is_string($teams)) {
            // maybe it's a string containing several teams separated by spaces
            return $Teams->getTeamsFromIdOrNameOrOrgidArray(explode(',', $teams));
        }
        throw new ImproperActionException('Could not find team ID to assign user!');
    }

    private function getTeams(): array | int
    {
        $teams = $this->samlUserdata[$this->settings['idp']['teamAttr'] ?? 'Nope'] ?? array();

        // if no team attribute is sent by the IDP, use the default team
        if (empty($teams)) {
            // we directly get the id from the stored config
            $teamId = $this->configArr['saml_team_default'];
            if ($teamId === '0') {
                throw new ImproperActionException('Could not find team ID to assign user!');
            }
            // this setting is when we want to allow the user to make team selection
            if ($teamId === '-1') {
                return self::TEAM_SELECTION_REQUIRED;
            }
            return array((int) $teamId);
        }

        if (is_array($teams)) {
            return ($teams);
        }

        if (is_string($teams)) {
            // maybe it's a string containing several teams separated by commas
            return explode(',', $teams);
        }
        throw new ImproperActionException('Could not find team ID to assign user!');
    }

    private function getUsers(string $email): Users | int
    {
        try {
            $Users = ExistingUser::fromEmail($email);
        } catch (ResourceNotFoundException) {
            // the user doesn't exist yet in the db
            // what do we do? Lookup the config setting for that case
            if ($this->configArr['saml_user_default'] === '0') {
                throw new ImproperActionException('Could not find an existing user. Ask a Sysadmin to create your account.');
            }

            // now try and get the teams
            $teams = $this->getTeams();

            // when we want to allow user to select a team before account is created
            if (is_int($teams)) {
                return $teams;
            }

            // CREATE USER (and force validation of user, with user permissions)
            $Users = ValidatedUser::fromExternal($email, $teams, $this->getName(), $this->getName(true));
        }
        return $Users;
    }
}
