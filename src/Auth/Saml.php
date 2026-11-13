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
use Elabftw\Elabftw\Authentication;
use Elabftw\Elabftw\Env;
use Elabftw\Enums\AuthMethod;
use Elabftw\Enums\Messages;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\Teams;
use Elabftw\Models\Users\ExistingUser;
use Elabftw\Models\Users\Users;
use Elabftw\Models\Users\ValidatedUser;
use Elabftw\Params\UserParams;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use OneLogin\Saml2\Auth as SamlAuthLib;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\NativeClock;

use function _;
use function array_filter;
use function array_values;
use function implode;
use function is_array;
use function is_string;
use function sprintf;
use function trim;

/**
 * Process a SAML assertion and resolve it to a local authentication.
 *
 * Starting the SAML login redirect belongs to LoginController. This class only
 * handles the IdP response, local-user provisioning/synchronization, and the
 * SAML session token used for logout.
 */
final class Saml
{
    private const string UNKNOWN_VALUE = 'Unknown';

    private const string TOKEN_AUDIENCE = 'saml-session';

    private array $samlUserdata = array();

    private ?string $samlSessionIdx = null;

    public function __construct(
        private readonly SamlAuthLib $SamlAuthLib,
        private readonly array $configArr,
        private readonly array $settings,
        private readonly LoggerInterface $logger,
    ) {}

    public static function getJWTConfig(): Configuration
    {
        $secretKey = Key::loadFromAsciiSafeString(Env::asString('SECRET_KEY'));

        /** @psalm-suppress ArgumentTypeCoercion */
        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($secretKey->getRawBytes()), // @phpstan-ignore-line
        );

        $config->setValidationConstraints(
            new PermittedFor(self::TOKEN_AUDIENCE),
            new SignedWith($config->signer(), $config->signingKey()),
            new LooseValidAt(new NativeClock()),
        );

        return $config;
    }

    public function encodeToken(int $idpId): string
    {
        $now = new DateTimeImmutable();
        $config = self::getJWTConfig();

        $token = $config->builder()
            ->permittedFor(self::TOKEN_AUDIENCE)
            ->issuedAt($now)
            /** @psalm-suppress PossiblyFalseArgument */
            ->expiresAt($now->modify('+1 month'))
            ->withClaim('sid', $this->getSessionIndex())
            ->withClaim('idp_id', $idpId)
            ->withClaim('nameid', $this->SamlAuthLib->getNameId())
            ->withClaim('nameid_format', $this->SamlAuthLib->getNameIdFormat())
            ->getToken($config->signer(), $config->signingKey());

        return $token->toString();
    }

    public static function decodeToken(string $token): array
    {
        $config = self::getJWTConfig();

        try {
            if ($token === '') {
                throw new UnauthorizedException(_('Decoding JWT Token failed'));
            }

            $parsedToken = $config->parser()->parse($token);
            if (!$parsedToken instanceof UnencryptedToken) {
                throw new UnauthorizedException(_('Decoding JWT Token failed'));
            }

            $config->validator()->assert(
                $parsedToken,
                ...$config->validationConstraints(),
            );

            return array(
                $parsedToken->claims()->get('sid'),
                $parsedToken->claims()->get('idp_id'),
                $parsedToken->claims()->get('nameid'),
                $parsedToken->claims()->get('nameid_format'),
            );
        } catch (CannotDecodeContent | InvalidTokenStructure | RequiredConstraintsViolated) {
            throw new UnauthorizedException(_('Decoding JWT Token failed'));
        }
    }

    public function assertIdpResponse(string $requestId): Authentication|InitialTeamSelectionRequired
    {
        $this->assertValidResponse($requestId);

        $this->samlUserdata = $this->SamlAuthLib->getAttributes();
        $this->samlSessionIdx = $this->SamlAuthLib->getSessionIndex();

        $email = $this->getEmail();
        $orgid = $this->readAttribute('orgidAttr');
        $orcid = $this->readAttribute('orcidAttr');
        $firstname = $this->readAttribute('fnameAttr', self::UNKNOWN_VALUE);
        $lastname = $this->readAttribute('lnameAttr', self::UNKNOWN_VALUE);

        $user = $this->getExistingUser($email, $orgid);

        if ($user === false) {
            $user = $this->provisionNewUser($email, $firstname, $lastname, $orgid, $orcid);
            if ($user instanceof InitialTeamSelectionRequired) {
                return $user;
            }
        }

        $this->synchronizeTeams($user);
        $this->synchronizeUserAttributes(
            $user,
            $firstname,
            $lastname,
            $orgid,
            $orcid,
        );

        return new Authentication(
            $user->getUserid(),
            AuthMethod::Saml,
        );
    }

    public function getSessionIndex(): ?string
    {
        return $this->samlSessionIdx;
    }

    private function assertValidResponse(string $requestId): void
    {
        $this->SamlAuthLib->processResponse($requestId);
        $errors = $this->SamlAuthLib->getErrors();

        if (!empty($errors)) {
            $details = implode(', ', $errors);
            $reason = $this->SamlAuthLib->getLastErrorReason();
            if (is_string($reason) && $reason !== '') {
                $details .= sprintf(' (reason: %s)', $reason);
            }
            // always log the details server side so sysadmins can troubleshoot without enabling debug mode
            $this->logger->error('SAML response validation failed', array(
                'errors' => $details,
                'idp' => $this->getIdpEntityId(),
                'requestId' => $requestId,
            ));

            $error = Messages::GenericError->toHuman();
            if ($this->isEnabled('saml_debug')) {
                $error = $details;
            }

            throw new UnauthorizedException($error);
        }

        if (!$this->SamlAuthLib->isAuthenticated()) {
            $this->logger->error('SAML authentication with IDP failed: response is valid but user is not authenticated', array(
                'idp' => $this->getIdpEntityId(),
                'requestId' => $requestId,
            ));
            throw new UnauthorizedException(_('Authentication with IDP failed!'));
        }
    }

    private function getIdpEntityId(): string
    {
        $entityId = $this->settings['idp']['entityId'] ?? null;
        return is_string($entityId) && $entityId !== '' ? $entityId : self::UNKNOWN_VALUE;
    }

    private function getEmail(): string
    {
        $emailAttr = $this->getAttributeName('emailAttr');
        if ($emailAttr === null) {
            throw new ImproperActionException(_('No email attribute configured for the IDP! Aborting.'));
        }

        return $this->readAttribute('emailAttr') ?? throw new ImproperActionException(sprintf(
            _('Could not find attribute "%s" in response from IDP! Aborting.'),
            $emailAttr,
        ));
    }

    /**
     * Name of the IdP attribute configured under this settings key
     */
    private function getAttributeName(string $settingKey): ?string
    {
        $attribute = $this->settings['idp'][$settingKey] ?? null;
        return is_string($attribute) && $attribute !== '' ? $attribute : null;
    }

    /**
     * First value of a single-valued IdP attribute, or the fallback when it is not configured, missing or empty
     *
     * @template T of string|null
     * @param T $fallback
     * @return string|T
     */
    private function readAttribute(string $settingKey, ?string $fallback = null): ?string
    {
        $attribute = $this->getAttributeName($settingKey);
        if ($attribute === null) {
            return $fallback;
        }

        $value = $this->samlUserdata[$attribute] ?? null;
        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        return is_string($value) && $value !== '' ? $value : $fallback;
    }

    private function provisionNewUser(
        string $email,
        string $firstname,
        string $lastname,
        ?string $orgid,
        ?string $orcid,
    ): Users|InitialTeamSelectionRequired {
        if (!$this->isEnabled('saml_user_default')) {
            throw new ImproperActionException($this->getAccountCreationDisabledMessage());
        }

        $teams = $this->getTeamsForNewUser();
        if ($teams === null) {
            return new InitialTeamSelectionRequired(
                email: $email,
                firstname: $firstname,
                lastname: $lastname,
                orgid: $orgid,
                orcid: $orcid,
            );
        }

        return ValidatedUser::fromExternal(
            $email,
            $teams,
            $firstname,
            $lastname,
            orgid: $orgid,
            orcid: $orcid,
            allowTeamCreation: $this->allowTeamCreation(),
        );
    }

    private function getAccountCreationDisabledMessage(): string
    {
        $message = $this->configArr['user_msg_need_local_account_created'] ?? null;
        if (is_string($message) && $message !== '') {
            return $message;
        }
        return _('Could not find an existing user. Ask a Sysadmin to create your account.');
    }

    /**
     * Return teams for a new local user.
     *
     * A null return means the IdP did not provide a usable team and the instance is
     * configured to let the user choose their initial team.
     *
     * @return list<mixed>|null
     */
    private function getTeamsForNewUser(): ?array
    {
        $teams = $this->getTeamsFromAttribute();

        if (empty($teams) && $this->isTeamSyncEnabled()) {
            // synchronization would remove the user from any team assigned here and fail on every login
            throw new ImproperActionException(
                _('Could not find team(s) in IDP response!'),
            );
        }

        $teamId = $this->getDefaultTeamId();

        if (empty($teams)) {
            if ($teamId === 0) {
                throw new ImproperActionException(
                    _('Could not find team ID to assign user!'),
                );
            }

            if ($teamId === -1) {
                return null;
            }

            return array($teamId);
        }

        // The team attribute is often prefilled from federation metadata: when its value matches no team
        // and teams cannot be created, let the user pick one instead of failing the login.
        // Not with team synchronization: the next login would try to resolve the same unknown team and fail.
        if (
            $teamId === -1
            && !$this->isTeamSyncEnabled()
            && !$this->allowTeamCreation()
            && !$this->anyTeamExists($teams)
        ) {
            return null;
        }

        return $teams;
    }

    /**
     * @param list<mixed> $teams
     */
    private function anyTeamExists(array $teams): bool
    {
        try {
            new Teams(new Users())->getTeamsFromIdOrNameOrOrgidArray($teams);
        } catch (ImproperActionException) {
            return false;
        }
        return true;
    }

    private function synchronizeTeams(Users $user): void
    {
        if (!$this->isTeamSyncEnabled()) {
            return;
        }

        new Teams($user)->synchronize(
            $user->getUserid(),
            $this->getTeamsFromIdpResponse(),
        );
    }

    private function getTeamsFromIdpResponse(): array
    {
        if ($this->getAttributeName('teamAttr') === null) {
            throw new ImproperActionException(
                _('Cannot synchronize team(s) from IDP if no value is set for looking up team(s) in IDP response!'),
            );
        }

        $teams = $this->getTeamsFromAttribute();
        if (empty($teams)) {
            throw new ImproperActionException(
                _('Could not find team(s) in IDP response!'),
            );
        }

        return new Teams(new Users())->getTeamsFromIdOrNameOrOrgidArray(
            $teams,
            $this->allowTeamCreation(),
        );
    }

    /**
     * Teams sent by the IdP, without blank values
     *
     * @return list<mixed>
     */
    private function getTeamsFromAttribute(): array
    {
        $teamAttribute = $this->getAttributeName('teamAttr');
        $teams = $teamAttribute === null ? null : $this->samlUserdata[$teamAttribute] ?? null;

        if (is_string($teams)) {
            $teams = array($teams);
        }

        if ($teams !== null && !is_array($teams)) {
            throw new ImproperActionException(
                _('Invalid team attribute returned by IDP.'),
            );
        }

        // SAML attributes are multi-valued, so an IdP with nothing to send can still send array('')
        return array_values(array_filter(
            $teams ?? array(),
            static fn(mixed $team): bool => !is_string($team) || trim($team) !== '',
        ));
    }

    private function getExistingUser(string $email, ?string $orgid = null): Users|false
    {
        try {
            return ExistingUser::fromEmail($email);
        } catch (ResourceNotFoundException) {
            if (!$this->isEnabled('saml_fallback_orgid') || $orgid === null) {
                return false;
            }

            try {
                $user = ExistingUser::fromOrgid($orgid);
            } catch (ResourceNotFoundException) {
                return false;
            }

            if ($this->isEnabled('saml_sync_email_idp')) {
                $user->update(new UserParams('email', $email));
            }

            return $user;
        }
    }

    private function synchronizeUserAttributes(
        Users $user,
        string $firstname,
        string $lastname,
        ?string $orgid,
        ?string $orcid,
    ): void {
        if (
            $firstname !== self::UNKNOWN_VALUE
            && $lastname !== self::UNKNOWN_VALUE
        ) {
            $user->update(new UserParams('firstname', $firstname));
            $user->update(new UserParams('lastname', $lastname));
        }

        if ($orgid !== null) {
            $user->update(new UserParams('orgid', $orgid));
        }

        if ($orcid !== null) {
            $user->update(new UserParams('orcid', $orcid));
        }
    }

    /**
     * Read a boolean setting from the instance config, stored as '0' or '1'
     */
    private function isEnabled(string $key, string $default = '0'): bool
    {
        return ($this->configArr[$key] ?? $default) === '1';
    }

    private function isTeamSyncEnabled(): bool
    {
        return $this->isEnabled('saml_sync_teams');
    }

    private function allowTeamCreation(): bool
    {
        return $this->isEnabled('saml_team_create', '1');
    }

    /**
     * Team for new users without a usable team from the IdP: 0 rejects them, -1 lets them choose
     */
    private function getDefaultTeamId(): int
    {
        return (int) ($this->configArr['saml_team_default'] ?? 0);
    }
}
