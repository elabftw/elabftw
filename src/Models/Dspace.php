<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Models;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Elabftw\Env;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Services\HttpGetter;
use GuzzleHttp\Client;
use Override;

use function str_starts_with;
use function rtrim;

/**
 * Connect with DSpace Repository
 * https://dspace.org/
 */
final class Dspace extends AbstractRest
{
    private string $baseUrl;

    private HttpGetter $HttpGetter;

    public function __construct()
    {
        parent::__construct();
        $Config = Config::getConfig();
        $this->baseUrl = rtrim($Config->configArr['dspace_host'] ?? '', '/') . '/';
        $proxy = Env::asBool('DSPACE_USE_PROXY') ? $Config->configArr['proxy'] : '';
        $this->HttpGetter = new HttpGetter(new Client(), $proxy, Env::asBool('DEV_MODE'));
    }

    #[Override]
    public function getApiPath(): string
    {
        return 'api/v2/dspace';
    }

    /**
     * Get method for /api/v2/dspace
     * $queryParams 'dspace_action' can be auth, collections, types.
     */
    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $action = 'auth';
        if ($queryParams !== null && $queryParams->getQuery()->has('dspace_action')) {
            $action = $queryParams->getQuery()->getString('dspace_action');
        }
        return match ($action) {
            'auth' => $this->getDspaceToken(),
            'collections' => $this->listCollections(),
            'types' => $this->listTypes(),
            default => throw new ImproperActionException('Unknown DSpace GET action.'),
        };
    }
    /*
     * GET METHODS
     * getDspaceToken, listCollections, listTypes, getItemUuid
     */
    private function getDspaceToken(): array
    {
        $Config = Config::getConfig();
        $user = $Config->configArr['dspace_user'] ?? '';
        $encPassword = $Config->configArr['dspace_password'] ?? '';
        if ($this->baseUrl === '' || $user === '' || $encPassword === '') {
            throw new ImproperActionException('DSpace config is incomplete.');
        }
        $password = Crypto::decrypt($encPassword, Key::loadFromAsciiSafeString(Env::asString('SECRET_KEY')));
        // first, get CSRF token
        $csrfRes = $this->HttpGetter->getWithHeaders($this->baseUrl . 'security/csrf');
        $xsrfToken = $csrfRes['headers']['DSPACE-XSRF-TOKEN'][0] ?? '';
        $cookies = $csrfRes['headers']['Set-Cookie'] ?? array();
        $cookieHeader = array();
        $dspaceXsrfCookie = null;

        foreach ($cookies as $cookieLine) {
            $parts = explode(';', $cookieLine);
            if (!isset($parts[0])) {
                continue;
            }
            $nv = trim($parts[0]);
            // Deduplicate DSPACE cookie
            if (str_starts_with($nv, 'DSPACE-XSRF-COOKIE=')) {
                if ($dspaceXsrfCookie === null) {
                    $dspaceXsrfCookie = $nv;
                    $cookieHeader[] = $nv;
                }
            }
        }
        // finally build headers
        $headers = array('Content-Type' => 'application/x-www-form-urlencoded');
        if ($cookieHeader) {
            $headers['Cookie'] = implode('; ', $cookieHeader);
        }
        if ($xsrfToken !== '') {
            $headers['X-XSRF-TOKEN'] = $xsrfToken;
        }

        // second, Login
        $loginRes = $this->HttpGetter->post($this->baseUrl . 'authn/login', array(
            'headers' => $headers, 'form_params' => array('user' => $user, 'password' => $password),
        ));
        if ($loginRes->getStatusCode() < 200 || $loginRes->getStatusCode() >= 300) {
            $body = (string) $loginRes->getBody();
            throw new ImproperActionException(sprintf(_('DSpace login failed: %s - %s'), $loginRes->getStatusCode(), $body));
        }

        // now Dspace returns an Authorization header we can re-use from JS
        $auth = $loginRes->getHeaderLine('Authorization');
        if ($auth === '') {
            throw new ImproperActionException(_('DSpace login did not return an Authorization header.'));
        }
        return array('auth' => $auth, 'xsrf' => $xsrfToken);
    }

    private function listCollections(): array
    {
        $res = $this->HttpGetter->getWithHeaders(
            $this->baseUrl . 'core/collections',
        );
        return json_decode($res['body'], true);
    }

    private function listTypes(): array
    {
        $res = $this->HttpGetter->getWithHeaders(
            $this->baseUrl . 'submission/vocabularies/common_types/entries',
        );
        return json_decode($res['body'], true);
    }

    private function getItemUuid(array $reqBody): int
    {
        $workspaceId = $reqBody['workspaceId'] ?? '';
        $headers = $reqBody['headers'] ?? array();
        if ($workspaceId === '') {
            throw new ImproperActionException('Missing workspaceId');
        }
        $res = $this->HttpGetter->getWithHeaders(
            $this->baseUrl . 'submission/workspaceitems/' . ($workspaceId) . '/item',
            $headers
        );
        $data = json_decode($res['body'], true, 512, JSON_THROW_ON_ERROR);
        if (!isset($data['uuid'])) {
            throw new ImproperActionException('DSpace did not return an item UUID.');
        }
        return (int) $data['uuid'];
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        return $this->exportItem($reqBody);
    }

    /*
     * POST METHODS
     * exportItem, createItem, saveToEntry, acceptLicense, updateMetadata, uploadEntryAsFile, submitItem
     */
    private function exportItem(array $reqBody): int
    {
        $collection = $reqBody['collection'] ?? '';
        $metadata = $reqBody['metadata'] ?? array();

        if ($collection === '' || empty($metadata)) {
            throw new ImproperActionException('Missing required export fields.');
        }

        // Step 1: Authenticate with DSpace
        $authHeaders = $this->getDspaceToken();
        $headers = array(
            'Authorization' => $authHeaders['auth'],
            'X-XSRF-TOKEN' => $authHeaders['xsrf'],
            'Content-Type' => 'application/json',
            'Cookie' => 'DSPACE-XSRF-COOKIE=' . $authHeaders['xsrf'],
        );

        // Step 2: Create workspace item
        $workspaceId = $this->createItem(array(
            'collection' => $collection,
            'metadata' => $metadata,
            'headers' => $headers,
        ));

        $headersFormatted = array(
            'Authorization'   => $authHeaders['auth'],
            'X-XSRF-TOKEN'    => $authHeaders['xsrf'],
            'Cookie'          => 'DSPACE-XSRF-COOKIE=' . $authHeaders['xsrf'],
        );
        // Step 3: Get item UUID
        return $this->getItemUuid(array('workspaceId' => $workspaceId, 'headers' => $headersFormatted));
        // Step 4: Save UUID to eLabFTW entry (to be implemented)
        $this->saveToEntry($uuid);

        // Step 5: Accept license
        $this->acceptLicense($workspaceId, $headers);

        // Step 6: Update metadata
        $this->updateMetadata($workspaceId, $metadata, $headers);

        // Step 7: Upload ELN file (to be implemented)
        $this->uploadEntryAsFile($workspaceId, $headers);

        // Step 8: Submit to workflow
        $this->submitItem($workspaceId, $headers);
    }
    //    private function acceptLicense(int $workspaceId, array $headers): void { /* ... */ }
    //    private function updateMetadata(int $workspaceId, array $metadata, array $headers): void { /* ... */ }
    //    private function uploadEntryAsFile(int $workspaceId, array $headers): void { /* ... */ }
    //    private function submitItem(int $workspaceId, array $headers): void { /* ... */ }

    private function createItem(array $reqBody): int
    {
        $collection = $reqBody['collection'] ?? '';
        $metadata = $reqBody['metadata'] ?? array();
        $incomingHeaders = $reqBody['headers'] ?? array();

        if ($collection === '' || empty($metadata)) {
            throw new ImproperActionException('Missing collection or metadata for workspace item creation.');
        }

        $url = $this->baseUrl . 'submission/workspaceitems?owningCollection=' . $collection;

        $headers = array(
            'Authorization' => $incomingHeaders['Authorization'] ?? '',
            'X-XSRF-TOKEN' => $incomingHeaders['X-XSRF-TOKEN'] ?? '',
            'Content-Type' => 'application/json',
            'Cookie' => $incomingHeaders['Cookie'] ?? '',
        );

        $res = $this->HttpGetter->post($url, array(
            'headers' => $headers,
            'json'    => $metadata,
        ));

        $body = $res->getBody()->getContents();
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        if (!isset($data['id'])) {
            throw new ImproperActionException('DSpace response did not return a workspace item ID.');
        }

        return (int) $data['id'];
    }

    private function updateMetadata(array $reqBody): int
    {
        return 1;
    }

    #[Override]
    public function readOne(): array
    {
        return array();
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        return array();
    }

    #[Override]
    public function destroy(): bool
    {
        throw new ImproperActionException('Not supported for DSpace.');
    }

    private function saveToEntry(string $uuid): void
    {
        $Entry = new Entries(Users::getCurrentUser());
        $entryId = $this->Request->getQuery()->getInt('id'); // assuming ?id=123 in the request

        if ($entryId === 0) {
            throw new ImproperActionException('Missing entry ID in request');
        }

        $entry = $Entry->readOne($entryId);

        $extra = $entry['extra'] ?? [];
        $extra['dspace_uuid'] = $uuid;

        $Entry->update($entryId, ['extra' => $extra]);
    }
}
