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
use Elabftw\Elabftw\Env;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Interfaces\AuthResponseInterface;
use Elabftw\Models\Teams;
use Elabftw\Models\Users\ExistingUser;
use Elabftw\Models\Users\Users;
use Elabftw\Models\Users\ValidatedUser;
use Elabftw\Params\UserParams;
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
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use Override;

use function base64_decode;
use function explode;
use function http_build_query;
use function is_array;
use function is_string;
use function json_decode;
use function parse_url;
use function sprintf;
use function strtr;

use const PHP_URL_HOST;

/**
 * OpenID Connect (OIDC) authentication service
 */
final class Oidc implements AuthInterface
{
    private const int TEAM_SELECTION_REQUIRED = 1;

    private const string UNKNOWN_VALUE = 'Unknown';

    private AuthResponseInterface $AuthResponse;

    private GenericProvider $provider;

    private array $oidcUserdata = array();

    private ?AccessToken $accessToken = null;

    private ?string $idToken = null;

    public function __construct(private array $configArr, private array $settings, private array &$sessionStorage)
    {
        $this->AuthResponse = new AuthResponse();

        try {
            // build OAuth2 provider configuration
            $scopeString = $settings['scope'] ?? 'openid email profile';
            
            $providerConfig = array(
                'clientId' => $settings['client_id'],
                'clientSecret' => $settings['client_secret'],
                'redirectUri' => $settings['redirect_uri'],
                'urlAuthorize' => $settings['authorization_endpoint'],
                'urlAccessToken' => $settings['token_endpoint'],
                'urlResourceOwnerDetails' => $settings['userinfo_endpoint'],
                'scopes' => explode(' ', $scopeString),
                'scopeSeparator' => ' ', // OIDC standard uses space separator
                'responseResourceOwnerId' => 'sub', // OIDC uses 'sub' not 'id'
            );

            $this->provider = new GenericProvider($providerConfig);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public static function getJWTConfig(): Configuration
    {
        $secretKey = Key::loadFromAsciiSafeString(Env::asString('SECRET_KEY'));
        /** @psalm-suppress ArgumentTypeCoercion */
        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($secretKey->getRawBytes()), // @phpstan-ignore-line
        );

        $config->setValidationConstraints(new SignedWith($config->signer(), $config->signingKey()));
        return $config;
    }

    public function encodeToken(int $idpId): string
    {
        $now = new DateTimeImmutable();
        $config = self::getJWTConfig();
        $token = $config->builder()
                // Configures the audience (aud claim)
                ->permittedFor('oidc-session')
                // Configures the time that the token was issue (iat claim)
                ->issuedAt($now)
                // Configures the expiration time of the token (exp claim)
                // @psalm-suppress PossiblyFalseArgument
                ->expiresAt($now->modify('+1 months'))
                // store IdP ID and ID token for logout
                ->withClaim('idp_id', $idpId)
                ->withClaim('id_token', $this->idToken)
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

            return array(
                $parsedToken->claims()->get('idp_id'),
                $parsedToken->claims()->get('id_token'),
            );
        } catch (CannotDecodeContent | InvalidTokenStructure | RequiredConstraintsViolated) {
            throw new UnauthorizedException('Decoding JWT Token failed');
        }
    }

    #[Override]
    public function tryAuth(): AuthResponseInterface
    {
        // generate authorization URL with state for CSRF protection
        $authUrl = $this->provider->getAuthorizationUrl();

        // store state in session to validate callback
        $this->sessionStorage['oauth2state'] = $this->provider->getState();

        // store IdP ID in session for callback
        $this->sessionStorage['oidc_idp_id'] = $this->settings['idp_id'] ?? 0;
        
        // redirect to IdP (this will exit)
        header('Location: ' . $authUrl);
        exit;
    }

    public function assertIdpResponse(string $code, string $state): AuthResponseInterface
    {
        // validate state to prevent CSRF
        if (!$this->validateState($state)) {
            throw new UnauthorizedException('Invalid state parameter. Possible CSRF attack.');
        }

        // exchange authorization code for access token
        try {
            $this->accessToken = $this->provider->getAccessToken('authorization_code', array(
                'code' => $code,
            ));
        } catch (IdentityProviderException $e) {
            throw new ImproperActionException(
                sprintf('Failed to obtain access token: %s', $e->getMessage())
            );
        } catch (\Exception $e) {
            throw new ImproperActionException(
                sprintf('Failed to obtain access token: %s', $e->getMessage())
            );
        }

        // get user information from IdP
        try {
            $resourceOwner = $this->provider->getResourceOwner($this->accessToken);
            $this->oidcUserdata = $resourceOwner->toArray();
        } catch (IdentityProviderException $e) {
            throw new ImproperActionException(
                sprintf('Failed to fetch user information: %s', $e->getMessage())
            );
        }

        // also try to parse ID token if available
        $idTokenClaims = $this->parseIdToken($this->accessToken);
        if ($idTokenClaims !== null) {
            // merge ID token claims with userinfo (ID token takes precedence)
            $this->oidcUserdata = array_merge($this->oidcUserdata, $idTokenClaims);
            // store raw ID token for logout
            $tokenValues = $this->accessToken->getValues();
            $this->idToken = $tokenValues['id_token'] ?? null;
        }

        //extract email from claims
        $email = $this->extractClaim($this->settings['email_claim'], true);
        if ($email === null) {
            throw new ImproperActionException('Email claim is required but was not provided by the IdP. Please ensure your IdP is configured to return the email claim with the openid and email scopes.');
        }
        // extract orgid if configured
        $orgid = $this->getOrgid();

        // get or create user
        $Users = $this->getUsers($email, $orgid);
        if (!$Users instanceof Users) {
            $this->AuthResponse->setAuthenticatedUserid(0);
            $this->AuthResponse->setInitTeamRequired(true);
            $this->AuthResponse->setInitTeamInfo(array(
                'email' => $email,
                'firstname' => $this->getName(),
                'lastname' => $this->getName(true),
                'orgid' => $orgid,
            ));
            return $this->AuthResponse;
        }

        $userid = $Users->userData['userid'];
        $this->AuthResponse->setAuthenticatedUserid($userid);

        // synchronize teams from IdP if configured
        if ($this->configArr['oidc_sync_teams'] === '1') {
            $claimName = $this->settings['team_claim'] ?? null;
            $teams = $claimName !== null ? ($this->oidcUserdata[$claimName] ?? null) : null;
            // Only sync if IdP actually sent teams data
            if (!empty($teams)) {
                try {
                    $Teams = new Teams($Users);
                    $Teams->synchronize($userid, $this->getTeamsFromIdpResponse());
                } catch (ImproperActionException $e) {
                    // If team sync fails, just skip it - don't prevent login
                    // User will keep their existing team memberships
                }
            }
        }

        // update user attributes with values from IdP
        $firstname = $this->getName();
        $lastname = $this->getName(true);
        if ($firstname !== self::UNKNOWN_VALUE && $lastname !== self::UNKNOWN_VALUE) {
            $Users->update(new UserParams('firstname', $firstname));
            $Users->update(new UserParams('lastname', $lastname));
        }
        if ($orgid !== null) {
            $Users->update(new UserParams('orgid', $orgid));
        }

        // load teams from database
        $UsersHelper = new UsersHelper($this->AuthResponse->getAuthUserid());
        $this->AuthResponse->setTeams($UsersHelper);

        return $this->AuthResponse;
    }

    public function getIdToken(): ?string
    {
        return $this->idToken;
    }

    /**
     * Get logout URL for RP-initiated logout
     * Not all OIDC providers support this endpoint
     */
    public function getLogoutUrl(?string $postLogoutRedirectUri = null): ?string
    {
        $endSessionEndpoint = $this->settings['end_session_endpoint'] ?? null;
        if ($endSessionEndpoint === null) {
            return null;
        }

        // build logout URL with optional parameters
        $params = array();
        if ($this->idToken !== null) {
            $params['id_token_hint'] = $this->idToken;
        }
        if ($postLogoutRedirectUri !== null) {
            $params['post_logout_redirect_uri'] = $postLogoutRedirectUri;
        }

        if (empty($params)) {
            return $endSessionEndpoint;
        }

        return $endSessionEndpoint . '?' . http_build_query($params);
    }

    /**
     * Validate state parameter from callback to prevent CSRF
     */
    private function validateState(string $receivedState): bool
    {
        $storedState = $this->sessionStorage['oauth2state'] ?? null;
        return $storedState !== null && $storedState === $receivedState;
    }

    /**
     * Parse and validate ID token (JWT) if present
     * Note: This is basic parsing without signature verification
     * For production use, consider full OIDC validation including signature verification against JWKS
     * @return array<string, mixed>|null
     */
    private function parseIdToken(AccessToken $token): ?array
    {
        $values = $token->getValues();
        if (!isset($values['id_token'])) {
            return null;
        }

        // basic JWT parsing without signature verification
        $parts = explode('.', $values['id_token']);
        if (count($parts) !== 3) {
            return null;
        }

        // decode payload (second part)
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        return is_array($payload) ? $payload : null;
    }

    /**
     * Extract a claim from OIDC userdata
     */
    private function extractClaim(string $claimName, bool $optional = false): ?string
    {
        if (!isset($this->oidcUserdata[$claimName])) {
            if ($optional) return null;
            $err = sprintf('Could not find claim "%s" in response from IdP! Aborting.', $claimName);
            throw new ImproperActionException($err);
        }
        $claim = $this->oidcUserdata[$claimName];

        // handle array values (take first element)
        if (is_array($claim)) {
            $claim = $claim[0] ?? null;
        }

        if ($claim === null || $claim === '') {
            if ($optional) return null;
            $err = sprintf('Could not find claim "%s" in response from IdP! Aborting.', $claimName);
            throw new ImproperActionException($err);
        }
        if (!isset($this->oidcUserdata[$claimName])) {
            if ($optional) return null;
            $err = sprintf('Could not find claim "%s" in response from IdP! Aborting.', $claimName);
            throw new ImproperActionException($err);
        }
        $claim = $this->oidcUserdata[$claimName];

        // handle array values (take first element)
        if (is_array($claim)) {
            $claim = $claim[0] ?? null;
        }

        if ($claim === null || $claim === '') {
            if ($optional) return null;
            $err = sprintf('Could not find claim "%s" in response from IdP! Aborting.', $claimName);
            throw new ImproperActionException($err);
        }
        if (!isset($this->oidcUserdata[$claimName])) {
            if ($optional) return null;
            $err = sprintf('Could not find claim "%s" in response from IdP! Aborting.', $claimName);
            throw new ImproperActionException($err);
        }
        $claim = $this->oidcUserdata[$claimName];

        // handle array values (take first element)
        if (is_array($claim)) {
            $claim = $claim[0] ?? null;
        }

        if ($claim === null || $claim === '') {
            if ($optional) return null;
            $err = sprintf('Could not find claim "%s" in response from IdP! Aborting.', $claimName);
            throw new ImproperActionException($err);
        }
        if (!isset($this->oidcUserdata[$claimName])) {
            if ($optional) return null;
            $err = sprintf('Could not find claim "%s" in response from IdP! Aborting.', $claimName);
            throw new ImproperActionException($err);
        }
        $claim = $this->oidcUserdata[$claimName];

        // handle array values (take first element)
        if (is_array($claim)) {
            $claim = $claim[0] ?? null;
        }

        if ($claim === null || $claim === '') {
            if ($optional) return null;
            $err = sprintf('Could not find claim "%s" in response from IdP! Aborting.', $claimName);
            throw new ImproperActionException($err);
        }
        if (!isset($this->oidcUserdata[$claimName])) {
            if ($optional) return null;
            $err = sprintf('Could not find claim "%s" in response from IdP! Aborting.', $claimName);
            throw new ImproperActionException($err);
        }
        $claim = $this->oidcUserdata[$claimName];

        // handle array values (take first element)
        if (is_array($claim)) {
            $claim = $claim[0] ?? null;
        }

        if ($claim === null || $claim === '') {
            if ($optional) return null;
            $err = sprintf('Could not find claim "%s" in response from IdP! Aborting.', $claimName);
            throw new ImproperActionException($err);
        }
        if (!isset($this->oidcUserdata[$claimName])) {
            if ($optional) return null;
            $err = sprintf('Could not find claim "%s" in response from IdP! Aborting.', $claimName);
            throw new ImproperActionException($err);
        }
        $claim = $this->oidcUserdata[$claimName];

        // handle array values (take first element)
        if (is_array($claim)) {
            $claim = $claim[0] ?? null;
        }

        if ($claim === null || $claim === '') {
            if ($optional) return null;
            $err = sprintf('Could not find claim "%s" in response from IdP! Aborting.', $claimName);
            throw new ImproperActionException($err);
        }
        if (!isset($this->oidcUserdata[$claimName])) {
            if ($optional) return null;
            $err = sprintf('Could not find claim "%s" in response from IdP! Aborting.', $claimName);
            throw new ImproperActionException($err);
        }
        $claim = $this->oidcUserdata[$claimName];

        // handle array values (take first element)
        if (is_array($claim)) {
            $claim = $claim[0] ?? null;
        }

        if ($claim === null || $claim === '') {
            if ($optional) return null;
            $err = sprintf('Could not find claim "%s" in response from IdP! Aborting.', $claimName);
            throw new ImproperActionException($err);
        }
        if (!isset($this->oidcUserdata[$claimName])) {
            if ($optional) return null;
            $err = sprintf('Could not find claim "%s" in response from IdP! Aborting.', $claimName);
            throw new ImproperActionException($err);
        }
        $claim = $this->oidcUserdata[$claimName];

        // handle array values (take first element)
        if (is_array($claim)) {
            $claim = $claim[0] ?? null;
        }

        if ($claim === null || $claim === '') {
            if ($optional) return null;
            $err = sprintf('Could not find claim "%s" in response from IdP! Aborting.', $claimName);
            throw new ImproperActionException($err);
        }
        if (!isset($this->oidcUserdata[$claimName])) {
            if ($optional) return null;
            $err = sprintf('Could not find claim "%s" in response from IdP! Aborting.', $claimName);
            throw new ImproperActionException($err);
        }
        $claim = $this->oidcUserdata[$claimName];

        // handle array values (take first element)
        if (is_array($claim)) {
            $claim = $claim[0] ?? null;
        }

        if ($claim === null || $claim === '') {
            if ($optional) return null;
            $err = sprintf('Could not find claim "%s" in response from IdP! Aborting.', $claimName);
            throw new ImproperActionException($err);
        }
        if (!isset($this->oidcUserdata[$claimName])) {
            if ($optional) return null;
            $err = sprintf('Could not find claim "%s" in response from IdP! Aborting.', $claimName);
            throw new ImproperActionException($err);
        }
        $claim = $this->oidcUserdata[$claimName];

        // handle array values (take first element)
        if (is_array($claim)) {
            $claim = $claim[0] ?? null;
        }

        if ($claim === null || $claim === '') {
            if ($optional) return null;
            $err = sprintf('Could not find claim "%s" in response from IdP! Aborting.', $claimName);
            throw new ImproperActionException($err);
        }

        if ($claim === null || $claim === '') {
            throw new ImproperActionException($err);
        }
        return (string) $claim;
    }

    private function getOrgid(): ?string
    {
        $claimName = $this->settings['orgid_claim'] ?? null;
        if ($claimName === null) {
            return null;
        }

        $orgid = $this->oidcUserdata[$claimName] ?? null;
        if (is_array($orgid)) {
            return $orgid[0] ?? null;
        }
        return $orgid;
    }

    /**
     * Get firstname or lastname from IdP claims
     */
    private function getName(bool $last = false): string
    {
        // toggle firstname or lastname claim
        $claimName = $last
            ? ($this->settings['lname_claim'] ?? 'family_name')
            : ($this->settings['fname_claim'] ?? 'given_name');

        $name = $this->oidcUserdata[$claimName] ?? self::UNKNOWN_VALUE;
        if (is_array($name)) {
            return $name[0] ?? self::UNKNOWN_VALUE;
        }
        return $name;
    }

    private function getTeamsFromIdpResponse(): array
    {
        $claimName = $this->settings['team_claim'] ?? null;
        if ($claimName === null) {
            throw new ImproperActionException('Cannot synchronize team(s) from IdP if no team claim is configured!');
        }

        $teams = $this->oidcUserdata[$claimName] ?? null;
        if (empty($teams)) {
            throw new ImproperActionException('Could not find team(s) in IdP response!');
        }

        // Normalize teams to be an array of scalar values
        if (!is_array($teams)) {
            $teams = array($teams);
        }

        $Teams = new Teams(new Users());
        $allowTeamCreation = ($this->configArr['oidc_team_create'] ?? '1') === '1';
        return $Teams->getTeamsFromIdOrNameOrOrgidArray($teams, $allowTeamCreation);
    }

    private function getTeams(): array | int
    {
        $claimName = $this->settings['team_claim'] ?? null;
        $teams = $claimName !== null ? ($this->oidcUserdata[$claimName] ?? array()) : array();

        // if no team claim is sent by the IdP, use the default team
        if (empty($teams)) {
            // get the id from stored config
            $teamId = $this->configArr['oidc_team_default'];
            // if no default team is configured, require team selection
            if ($teamId === '0' || $teamId === '-1') {
                return self::TEAM_SELECTION_REQUIRED;
            }
            return array((int) $teamId);
        }
        if (is_string($teams)) {
            return array($teams);
        }

        return $teams;
    }

    private function getExistingUser(string $email, ?string $orgid = null): Users | false
    {
        try {
            // first try to match a local user with the email
            return ExistingUser::fromEmail($email);
        } catch (ResourceNotFoundException) {
            // try finding the user with the orgid if email didn't work
            // but only if explicitly configured
            if ($this->configArr['oidc_fallback_orgid'] === '1' && $orgid) {
                try {
                    $Users = ExistingUser::fromOrgid($orgid);
                    // found user via orgid, maybe update stored email
                    if ($this->configArr['oidc_sync_email_idp'] === '1') {
                        $Users->update(new UserParams('email', $email));
                    }
                    return $Users;
                } catch (ResourceNotFoundException) {
                    return false;
                }
            }
            return false;
        }
    }

    private function getUsers(string $email, ?string $orgid = null): Users | int
    {
        $Users = $this->getExistingUser($email, $orgid);
        if ($Users === false) {
            // user doesn't exist yet in the database
            // check config setting for this case
            if ($this->configArr['oidc_user_default'] === '0') {
                $msg = _('Could not find an existing user. Ask a Sysadmin to create your account.');
                if ($this->configArr['user_msg_need_local_account_created']) {
                    $msg = $this->configArr['user_msg_need_local_account_created'];
                }
                throw new ImproperActionException($msg);
            }

            // try to get teams
            $teams = $this->getTeams();

            if (is_int($teams)) {
                return $teams;
            }

            // Validate that we can actually find or create these teams
            // If we can't, require team selection instead of failing
            try {
                $allowTeamCreation = ($this->configArr['oidc_team_create'] ?? '1') === '1';
                $Teams = new Teams(new Users());
                $validatedTeams = $Teams->getTeamsFromIdOrNameOrOrgidArray($teams, $allowTeamCreation);
                // create user (force validation with user permissions)
                /** @psalm-suppress PossiblyInvalidArgument */
                $Users = ValidatedUser::fromExternal($email, $validatedTeams, $this->getName(), $this->getName(true), orgid: $orgid, allowTeamCreation: $allowTeamCreation);
            } catch (ImproperActionException $e) {
                // If we can't find/create any teams, require user to select teams
                return self::TEAM_SELECTION_REQUIRED;
            }
        }
        return $Users;
    }
}
