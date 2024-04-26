<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use DateTimeImmutable;
use Defuse\Crypto\Key;
use Elabftw\Elabftw\AuthResponse;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Models\Config;
use Elabftw\Models\ExistingUser;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;
use Elabftw\Models\ValidatedUser;
use Elabftw\Services\UsersHelper;
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

use function is_array;
use function is_int;

/**
 * SAML auth service
 */
class Saml implements AuthInterface
{
    private const int TEAM_SELECTION_REQUIRED = 1;

    private AuthResponse $AuthResponse;

    private array $samlUserdata = array();

    private ?string $samlSessionIdx;

    public function __construct(private SamlAuthLib $SamlAuthLib, private array $configArr, private array $settings)
    {
        $this->AuthResponse = new AuthResponse();
    }

    public static function getJWTConfig(): Configuration
    {
        $secretKey = Key::loadFromAsciiSafeString(Config::fromEnv('SECRET_KEY'));
        /** @psalm-suppress ArgumentTypeCoercion */
        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($secretKey->getRawBytes()), // @phpstan-ignore-line
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
                // @psalm-suppress PossiblyFalseArgument
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
            if (empty($token)) {
                throw new UnauthorizedException('Decoding JWT Token failed');
            }
            $parsedToken = $conf->parser()->parse($token);
            if (!$parsedToken instanceof UnencryptedToken) {
                throw new UnauthorizedException('Decoding JWT Token failed');
            }
            $conf->validator()->assert($parsedToken, ...$conf->validationConstraints());

            return array($parsedToken->claims()->get('sid'), $parsedToken->claims()->get('idp_id'));
        } catch (CannotDecodeContent | InvalidTokenStructure | RequiredConstraintsViolated) {
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
        $email = $this->extractAttribute($this->settings['idp']['emailAttr']);

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

        $userid = $Users->userData['userid'];

        $this->AuthResponse->userid = $userid;
        $this->AuthResponse->mfaSecret = $Users->userData['mfa_secret'];
        $this->AuthResponse->isValidated = (bool) $Users->userData['validated'];

        // synchronize the teams from the IDP
        // because teams can change since the time the user was created
        if ($this->configArr['saml_sync_teams']) {
            $Teams = new Teams($Users);
            $Teams->synchronize($userid, $this->getTeamsFromIdpResponse());
        }

        // load the teams from db
        $UsersHelper = new UsersHelper($this->AuthResponse->userid);
        $this->AuthResponse->setTeams($UsersHelper);

        return $this->AuthResponse;
    }

    public function getSessionIndex(): ?string
    {
        return $this->samlSessionIdx;
    }

    private function extractAttribute(string $attribute): string
    {
        $err = sprintf('Could not find attribute "%s" in response from IDP! Aborting.', $attribute);
        if (!isset($this->samlUserdata[$attribute])) {
            throw new ImproperActionException($err);
        }
        $attr = $this->samlUserdata[$attribute];

        if (is_array($attr)) {
            $attr = $attr[0];
        }

        if ($attr === null) {
            throw new ImproperActionException($err);
        }
        return $attr;
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
            return $teams;
        }

        if (is_string($teams)) {
            // maybe it's a string containing several teams separated by commas
            return explode(',', $teams);
        }
        throw new ImproperActionException('Could not find team ID to assign user!');
    }

    private function getExistingUser(string $email): Users | false
    {
        try {
            // we first try to match a local user with the email
            return ExistingUser::fromEmail($email);
        } catch (ResourceNotFoundException) {
            // try finding the user with the orgid because email didn't work
            // but only if we explicitly want to
            if ($this->configArr['saml_fallback_orgid'] === '1' && !empty($this->settings['idp']['orgidAttr'])) {
                $orgid = $this->extractAttribute($this->settings['idp']['orgidAttr']);
                try {
                    $Users = ExistingUser::fromOrgid($orgid);
                    // ok we found our user thanks to the orgid, maybe we want to update our stored email?
                    if ($this->configArr['saml_sync_email_idp'] === '1') {
                        $Users->patch(Action::Update, array('email' => $email));
                    }
                    return $Users;
                } catch (ResourceNotFoundException) {
                    return false;
                }
            }
            return false;
        }
    }

    private function getUsers(string $email): Users | int
    {
        $Users = $this->getExistingUser($email);
        if ($Users === false) {
            // the user doesn't exist yet in the db
            // what do we do? Lookup the config setting for that case
            if ($this->configArr['saml_user_default'] === '0') {
                $msg = _('Could not find an existing user. Ask a Sysadmin to create your account.');
                if ($this->configArr['user_msg_need_local_account_created']) {
                    $msg = $this->configArr['user_msg_need_local_account_created'];
                }
                throw new ImproperActionException($msg);
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
