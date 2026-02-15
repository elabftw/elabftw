<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Env;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\SetIdTrait;
use Override;
use PDO;

use function is_string;

/**
 * An OIDC IDP is an OpenID Connect Identity Provider
 */
final class IdpsOidc extends AbstractRest
{
    use SetIdTrait;

    public function __construct(private Users $requester, ?int $id = null)
    {
        parent::__construct();
        $this->setId($id);
    }

    #[Override]
    public function getApiPath(): string
    {
        return 'api/v2/idps_oidc/';
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        $this->requester->isSysadminOrExplode();
        return $this->create(
            name: $reqBody['name'],
            issuer: $reqBody['issuer'],
            client_id: $reqBody['client_id'],
            client_secret: $reqBody['client_secret'],
            authorization_endpoint: $reqBody['authorization_endpoint'] ?? '',
            token_endpoint: $reqBody['token_endpoint'] ?? '',
            userinfo_endpoint: $reqBody['userinfo_endpoint'] ?? '',
            end_session_endpoint: $reqBody['end_session_endpoint'] ?? null,
            jwks_uri: $reqBody['jwks_uri'] ?? null,
            scope: $reqBody['scope'] ?? 'openid email profile',
            email_claim: $reqBody['email_claim'] ?? 'email',
            fname_claim: $reqBody['fname_claim'] ?? 'given_name',
            lname_claim: $reqBody['lname_claim'] ?? 'family_name',
            team_claim: $reqBody['team_claim'] ?? null,
            orgid_claim: $reqBody['orgid_claim'] ?? null,
        );
    }

    #[Override]
    public function readOne(): array
    {
        $this->requester->isSysadminOrExplode();
        $sql = 'SELECT * FROM idps_oidc WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $this->Db->fetch($req);
        if (!empty($res['client_secret'])) {
            $res['client_secret'] = '******';
        }
        return $res;
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $this->requester->isSysadminOrExplode();
        $sql = 'SELECT id, name, issuer, enabled, created_at, modified_at FROM idps_oidc ORDER BY name ASC';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    /**
     * Get a list of enabled OIDC IDPs for the login page
     */
    public function readAllSimpleEnabled(): array
    {
        $sql = 'SELECT id, name, issuer FROM idps_oidc WHERE enabled = 1 ORDER BY name ASC';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    /**
     * Get the full configuration for an OIDC IDP for authentication
     * Decrypts the client_secret
     */
    public function getForAuth(int $id): array
    {
        $sql = 'SELECT * FROM idps_oidc WHERE id = :id AND enabled = 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $this->Db->fetch($req);
        
        if (empty($res)) {
            throw new ImproperActionException('OIDC provider not found or not enabled');
        }

        // decrypt client_secret
        if (!empty($res['client_secret'])) {
            $res['client_secret'] = $this->decryptSecret($res['client_secret']);
        }

        return $res;
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        $this->requester->isSysadminOrExplode();
        
        match ($action) {
            Action::Update => $this->update($params),
            Action::Unreference => $this->setId((int) $params['id']) && $this->toggleEnabled(),
            default => throw new ImproperActionException('Invalid action for OIDC IDP patch'),
        };

        return $this->readOne();
    }

    #[Override]
    public function destroy(): bool
    {
        $this->requester->isSysadminOrExplode();
        $sql = 'DELETE FROM idps_oidc WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function create(
        string $name,
        string $issuer,
        string $client_id,
        string $client_secret,
        string $authorization_endpoint,
        string $token_endpoint,
        string $userinfo_endpoint,
        ?string $end_session_endpoint = null,
        ?string $jwks_uri = null,
        string $scope = 'openid email profile',
        string $email_claim = 'email',
        string $fname_claim = 'given_name',
        string $lname_claim = 'family_name',
        ?string $team_claim = null,
        ?string $orgid_claim = null,
    ): int {
        // If endpoints are empty, attempt auto-discovery
        if (empty($authorization_endpoint) || empty($token_endpoint) || empty($userinfo_endpoint)) {
            $Config = Config::getConfig();
            $Idps = new Idps($this->requester);
            $helper = new \Elabftw\Elabftw\IdpsHelper($Config, $Idps);
            $discovered = $helper->discoverOidcEndpoints($issuer);
            
            $authorization_endpoint = $authorization_endpoint ?: ($discovered['authorization_endpoint'] ?? '');
            $token_endpoint = $token_endpoint ?: ($discovered['token_endpoint'] ?? '');
            $userinfo_endpoint = $userinfo_endpoint ?: ($discovered['userinfo_endpoint'] ?? '');
            $end_session_endpoint = $end_session_endpoint ?: $discovered['end_session_endpoint'];
            $jwks_uri = $jwks_uri ?: $discovered['jwks_uri'];
            
            // Validate required endpoints were discovered
            if (empty($authorization_endpoint) || empty($token_endpoint) || empty($userinfo_endpoint)) {
                throw new ImproperActionException('Failed to discover required OIDC endpoints. Please provide them manually.');
            }
        }

        // encrypt client_secret before storing
        $encrypted_secret = $this->encryptSecret($client_secret);

        $sql = 'INSERT INTO idps_oidc (
            name, issuer, client_id, client_secret, 
            authorization_endpoint, token_endpoint, userinfo_endpoint, 
            end_session_endpoint, jwks_uri, scope,
            email_claim, fname_claim, lname_claim, team_claim, orgid_claim
        ) VALUES (
            :name, :issuer, :client_id, :client_secret,
            :authorization_endpoint, :token_endpoint, :userinfo_endpoint,
            :end_session_endpoint, :jwks_uri, :scope,
            :email_claim, :fname_claim, :lname_claim, :team_claim, :orgid_claim
        )';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':issuer', $issuer);
        $req->bindParam(':client_id', $client_id);
        $req->bindParam(':client_secret', $encrypted_secret);
        $req->bindParam(':authorization_endpoint', $authorization_endpoint);
        $req->bindParam(':token_endpoint', $token_endpoint);
        $req->bindParam(':userinfo_endpoint', $userinfo_endpoint);
        $req->bindParam(':end_session_endpoint', $end_session_endpoint);
        $req->bindParam(':jwks_uri', $jwks_uri);
        $req->bindParam(':scope', $scope);
        $req->bindParam(':email_claim', $email_claim);
        $req->bindParam(':fname_claim', $fname_claim);
        $req->bindParam(':lname_claim', $lname_claim);
        $req->bindParam(':team_claim', $team_claim);
        $req->bindParam(':orgid_claim', $orgid_claim);
        
        $this->Db->execute($req);
        return $this->Db->lastInsertId();
    }

    private function update(array $params): bool
    {
        // Remove empty client_secret from params to keep existing one
        if (isset($params['client_secret']) && empty($params['client_secret'])) {
            unset($params['client_secret']);
        }
        
        $sql = 'UPDATE idps_oidc SET ';
        $sqlArr = array();

        foreach ($params as $key => $value) {
            // skip id and created_at
            if ($key === 'id' || $key === 'created_at') {
                continue;
            }
            
            // encrypt client_secret if provided
            if ($key === 'client_secret') {
                $value = $this->encryptSecret($value);
                $params[$key] = $value; // update in params for binding
            }
            
            $sqlArr[] = $key . ' = :' . $key;
        }

        if (empty($sqlArr)) {
            return true; // nothing to update
        }

        $sql .= implode(', ', $sqlArr);
        $sql .= ', modified_at = CURRENT_TIMESTAMP WHERE id = :id';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        foreach ($params as $key => $value) {
            if ($key === 'id' || $key === 'created_at') {
                continue;
            }
            
            $req->bindValue(':' . $key, $value);
        }

        return $this->Db->execute($req);
    }

    private function toggleEnabled(): bool
    {
        $sql = 'UPDATE idps_oidc SET enabled = IF(enabled = 1, 0, 1) WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function encryptSecret(string $secret): string
    {
        try {
            $key = Key::loadFromAsciiSafeString(Env::asString('SECRET_KEY'));
            return Crypto::encrypt($secret, $key);
        } catch (\Exception $e) {
            throw new ImproperActionException('Failed to encrypt client secret: ' . $e->getMessage());
        }
    }

    private function decryptSecret(string $encryptedSecret): string
    {
        try {
            $key = Key::loadFromAsciiSafeString(Env::asString('SECRET_KEY'));
            return Crypto::decrypt($encryptedSecret, $key);
        } catch (\Exception $e) {
            throw new ImproperActionException('Failed to decrypt client secret. The encryption key may have changed: ' . $e->getMessage());
        }
    }
}
