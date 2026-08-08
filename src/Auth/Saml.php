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
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use OneLogin\Saml2\Auth as SamlAuthLib;

use function _;
use function array_values;
use function implode;
use function is_array;
use function is_string;
use function sprintf;

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
                throw new UnauthorizedException('Decoding JWT Token failed');
            }

            $parsedToken = $config->parser()->parse($token);
            if (!$parsedToken instanceof UnencryptedToken) {
                throw new UnauthorizedException('Decoding JWT Token failed');
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
            throw new UnauthorizedException('Decoding JWT Token failed');
        }
    }

    public function assertIdpResponse(): Authentication|InitialTeamSelectionRequired
    {
        $this->assertValidResponse();

        $this->samlUserdata = $this->SamlAuthLib->getAttributes();
        $this->samlSessionIdx = $this->SamlAuthLib->getSessionIndex();

        $email = $this->extractAttribute(
            $this->settings['idp']['emailAttr'],
        );
        $orgid = $this->getAttribute('orgidAttr');
        $orcid = $this->getAttribute('orcidAttr');
        $firstname = $this->getName();
        $lastname = $this->getName(last: true);

        $user = $this->getExistingUser($email, $orgid);

        if ($user === false) {
            if (($this->configArr['saml_user_default'] ?? '0') === '0') {
                $message = _('Could not find an existing user. Ask a Sysadmin to create your account.');

                if (!empty($this->configArr['user_msg_need_local_account_created'])) {
                    $message = $this->configArr['user_msg_need_local_account_created'];
                }

                throw new ImproperActionException($message);
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

            $user = ValidatedUser::fromExternal(
                $email,
                $teams,
                $firstname,
                $lastname,
                orgid: $orgid,
                orcid: $orcid,
                allowTeamCreation: $this->allowTeamCreation(),
            );
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

    private function assertValidResponse(): void
    {
        $this->SamlAuthLib->processResponse();
        $errors = $this->SamlAuthLib->getErrors();

        if (!empty($errors)) {
            $error = Messages::GenericError->toHuman();
            if (($this->configArr['saml_debug'] ?? '0') === '1') {
                $error = implode(', ', $errors);
            }

            throw new UnauthorizedException($error);
        }

        if (!$this->SamlAuthLib->isAuthenticated()) {
            throw new UnauthorizedException('Authentication with IDP failed!');
        }
    }

    private function extractAttribute(string $attribute): string
    {
        $error = sprintf(
            'Could not find attribute "%s" in response from IDP! Aborting.',
            $attribute,
        );

        if (!isset($this->samlUserdata[$attribute])) {
            throw new ImproperActionException($error);
        }

        $value = $this->samlUserdata[$attribute];
        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        if (!is_string($value) || $value === '') {
            throw new ImproperActionException($error);
        }

        return $value;
    }

    private function getAttribute(string $key): ?string
    {
        $attribute = $this->settings['idp'][$key] ?? null;
        if (!is_string($attribute) || $attribute === '') {
            return null;
        }

        $value = $this->samlUserdata[$attribute] ?? null;
        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function getName(bool $last = false): string
    {
        $selector = $last ? 'lnameAttr' : 'fnameAttr';
        $attribute = $this->settings['idp'][$selector] ?? null;

        if (!is_string($attribute) || $attribute === '') {
            return self::UNKNOWN_VALUE;
        }

        $value = $this->samlUserdata[$attribute] ?? self::UNKNOWN_VALUE;
        if (is_array($value)) {
            $value = $value[0] ?? self::UNKNOWN_VALUE;
        }

        return is_string($value) ? $value : self::UNKNOWN_VALUE;
    }

    /**
     * Return teams for a new local user.
     *
     * A null return means the IdP did not provide teams and the instance is
     * configured to let the user choose their initial team.
     *
     * @return list<mixed>|null
     */
    private function getTeamsForNewUser(): ?array
    {
        $teamAttribute = $this->settings['idp']['teamAttr'] ?? null;
        $teams = null;

        if (is_string($teamAttribute) && $teamAttribute !== '') {
            $teams = $this->samlUserdata[$teamAttribute] ?? null;
        }

        if (empty($teams)) {
            $teamId = (int) ($this->configArr['saml_team_default'] ?? 0);

            if ($teamId === 0) {
                throw new ImproperActionException(
                    'Could not find team ID to assign user!',
                );
            }

            if ($teamId === -1) {
                return null;
            }

            return array($teamId);
        }

        if (is_string($teams)) {
            return array($teams);
        }

        if (!is_array($teams)) {
            throw new ImproperActionException(
                'Invalid team attribute returned by IDP.',
            );
        }

        return array_values($teams);
    }

    private function synchronizeTeams(Users $user): void
    {
        if (($this->configArr['saml_sync_teams'] ?? '0') !== '1') {
            return;
        }

        $teams = new Teams($user);
        $teams->synchronize(
            $user->getUserid(),
            $this->getTeamsFromIdpResponse(),
        );
    }

    private function getTeamsFromIdpResponse(): array
    {
        $teamAttribute = $this->settings['idp']['teamAttr'] ?? null;
        if (!is_string($teamAttribute) || $teamAttribute === '') {
            throw new ImproperActionException(
                'Cannot synchronize team(s) from IDP if no value is set for looking up team(s) in IDP response!',
            );
        }

        $teams = $this->samlUserdata[$teamAttribute] ?? null;
        if (empty($teams)) {
            throw new ImproperActionException(
                'Could not find team(s) in IDP response!',
            );
        }

        if (is_string($teams)) {
            $teams = array($teams);
        }

        if (!is_array($teams)) {
            throw new ImproperActionException(
                'Invalid team attribute returned by IDP.',
            );
        }

        return new Teams(new Users())->getTeamsFromIdOrNameOrOrgidArray(
            array_values($teams),
            $this->allowTeamCreation(),
        );
    }

    private function getExistingUser(
        string $email,
        ?string $orgid = null,
    ): Users|false {
        try {
            return ExistingUser::fromEmail($email);
        } catch (ResourceNotFoundException) {
            if (
                ($this->configArr['saml_fallback_orgid'] ?? '0') !== '1'
                || $orgid === null
            ) {
                return false;
            }

            try {
                $user = ExistingUser::fromOrgid($orgid);
            } catch (ResourceNotFoundException) {
                return false;
            }

            if (($this->configArr['saml_sync_email_idp'] ?? '0') === '1') {
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

    private function allowTeamCreation(): bool
    {
        return ($this->configArr['saml_team_create'] ?? '1') === '1';
    }
}
