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
use Elabftw\Enums\DspaceAction;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\Storage;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Make\MakeEln;
use Elabftw\Models\Users\Users;
use Elabftw\Services\HttpGetter;
use Override;
use ZipStream\ZipStream;

use function str_starts_with;
use function rtrim;
use function sprintf;
use function json_decode;

/**
 * Connect with DSpace Repository
 * https://dspace.org/
 */
final class Dspace extends AbstractRest
{
    private const METADATA_SECTIONS = array(
        'dc.contributor.author' => 'publicationStep',
        'dc.title' => 'publicationStep',
        'dc.date.issued' => 'publicationStep',
        'dc.type' => 'publicationStep',
        'dc.description.abstract' => 'traditionalpagetwo',
    );

    private ?array $headers = null;

    // rename host to host
    public function __construct(
        private readonly Users $requester,
        private readonly HttpGetter $httpGetter,
        string $host,
        private readonly string $user,
        private readonly string $encPassword,
    ) {
        parent::__construct();
        $host = rtrim($host, '/');
        $this->host = $host === '' ? '' : $host . '/server/api/';
    }

    #[Override]
    public function getApiPath(): string
    {
        return 'api/v2/dspace';
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        if ($queryParams === null || !$queryParams->getQuery()->has('dspace_action')) {
            return array();
        }
        $action = DspaceAction::tryFrom($queryParams->getQuery()->getString('dspace_action'))
            ?? throw new ImproperActionException('Unknown GET action for DSpace endpoint.');

        return match ($action) {
            DspaceAction::GetCollections => $this->getCollections(),
            DspaceAction::GetTypes => $this->getTypes(),
        };
    }

    /*
     * Create a new workspace in DSpace and return its Id.
     * It will allow for creation of the item inside that workspace.
     */
    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        return $this->createWorkspaceItem($reqBody);
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        $workspaceId = $this->postAction(Action::Create, $params);
        $uuid = $this->getItemUuid($workspaceId);
        $this->acceptLicense($workspaceId);
        $this->updateMetadata($workspaceId, $params['metadata'] ?? array());
        $this->uploadEntryAsFile($workspaceId, $params['entity'] ?? array());
        $this->submitToWorkflow($workspaceId);
        // return id and uuid for elab entry metadata
        return array('id' => $workspaceId, 'uuid' => $uuid);
    }

    #[Override]
    public function readOne(): array
    {
        return array();
    }

    #[Override]
    public function destroy(): bool
    {
        throw new ImproperActionException('Not supported for DSpace.');
    }

    // Cache auth information
    private function getAuthHeaders(): array
    {
        return $this->headers ??= $this->getToken();
    }

    private function getToken(): array
    {
        if ($this->user === '' || $this->encPassword === '') {
            throw new ImproperActionException('DSpace configuration is incomplete.');
        }
        $password = Crypto::decrypt($this->encPassword, Key::loadFromAsciiSafeString(Env::asString('SECRET_KEY')));
        // CSRF request + cookie parsing
        [$xsrfToken, $cookieHeader] = $this->fetchXsrfTokenAndCookieHeader();

        $headers = $this->buildLoginHeaders($xsrfToken, $cookieHeader);

        // login and get Authorization header
        $auth = $this->loginAndGetAuthHeader($headers, $password);
        return array(
            'Authorization' => $auth,
            'X-XSRF-TOKEN' => $xsrfToken,
            'Cookie' => 'DSPACE-XSRF-COOKIE=' . $xsrfToken,
        );
    }

    private function submitToWorkflow(int $workspaceId): void
    {
        $headers = $this->getAuthHeaders();
        $this->httpGetter->post($this->host . 'workflow/workflowitems', array(
            'headers' => array_merge($headers, array('Content-Type' => 'text/uri-list')),
            'body' => sprintf('/api/submission/workspaceitems/%d', $workspaceId),
        ));
    }

    private function fetchXsrfTokenAndCookieHeader(): array
    {
        $res = $this->httpGetter->get($this->host . 'security/csrf');
        $xsrfHeader = $res->getHeader('DSPACE-XSRF-TOKEN');
        $xsrfToken  = $xsrfHeader[0] ?? '';
        $cookies = $res->getHeader('Set-Cookie');
        $cookieHeader = array();
        $xsrfCookie = null;
        foreach ($cookies as $cookieLine) {
            $parts = explode(';', $cookieLine);
            if (!isset($parts[0])) {
                continue;
            }
            $nv = trim($parts[0]);
            // Deduplicate cookie
            if (str_starts_with($nv, 'DSPACE-XSRF-COOKIE=')) {
                if ($xsrfCookie === null) {
                    $xsrfCookie = $nv;
                    $cookieHeader[] = $nv;
                }
            }
        }
        return array($xsrfToken, $cookieHeader);
    }

    private function buildLoginHeaders(string $xsrfToken, array $cookieHeader): array
    {
        $headers = array('Content-Type' => 'application/x-www-form-urlencoded');
        if ($cookieHeader) {
            $headers['Cookie'] = implode('; ', $cookieHeader);
        }
        if ($xsrfToken !== '') {
            $headers['X-XSRF-TOKEN'] = $xsrfToken;
        }
        return $headers;
    }

    private function loginAndGetAuthHeader(array $headers, string $password): string
    {
        $loginRes = $this->httpGetter->post($this->host . 'authn/login', array(
            'headers' => $headers, 'form_params' => array('user' => $this->user, 'password' => $password),
        ));
        $auth = $loginRes->getHeaderLine('Authorization');
        if ($auth === '') {
            throw new ImproperActionException(_('DSpace login did not return an Authorization header.'));
        }
        return $auth;
    }

    private function getCollections(): array
    {
        $collections = array();
        $page = 0;
        $pageSize = 20;
        do {
            $res = $this->httpGetter->get($this->host . 'core/collections?page=' . $page . '&size=' . $pageSize);
            $body = json_decode($res->getBody()->getContents(), true);
            // append collections from each page
            if (isset($body['_embedded']['collections'])) {
                $collections = array_merge($collections, $body['_embedded']['collections']);
            }
            // stop if we're on the last page
            $totalPages = $body['page']['totalPages'] ?? 1;
            $page++;
        } while ($page < $totalPages);
        return $collections;
    }

    private function getTypes(): array
    {
        $res = $this->httpGetter->get($this->host . 'submission/vocabularies/common_types/entries');
        $body = $res->getBody()->getContents();
        return json_decode($body, true);
    }

    // 1. create the workspace in DSpace, returns the $workspaceId
    private function createWorkspaceItem(array $reqBody): int
    {
        $collection = $reqBody['collection'] ?? '';
        $metadata = $reqBody['metadata'] ?? array();
        if ($collection === '' || empty($metadata)) {
            throw new ImproperActionException('Missing required export fields.');
        }
        $headers = $this->getAuthHeaders();
        $headers['Content-Type'] = 'application/json';

        $url = sprintf('%ssubmission/workspaceitems?owningCollection=%s', $this->host, $collection);
        $res = $this->httpGetter->post($url, array('headers' => $headers, 'json' => $metadata));
        $body = $res->getBody()->getContents();
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        if (!isset($data['id']) || !is_int($data['id']) || $data['id'] <= 0) {
            throw new ImproperActionException('DSpace did not return a valid workspace ID.');
        }
        return $data['id'];
    }

    // 2. create the workspace in DSpace, returns the $workspaceId
    private function getItemUuid(int $workspaceId): string
    {
        $headers = $this->getAuthHeaders();
        $url = sprintf('%ssubmission/workspaceitems/%d/item', $this->host, $workspaceId);
        $res  = $this->httpGetter->get($url, $headers);
        $body = $res->getBody()->getContents();
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        if (!isset($data['uuid'])) {
            throw new ImproperActionException('DSpace did not return an item UUID.');
        }
        return $data['uuid'];
    }

    // 3. accept DSpace License, mandatory for submitting item
    private function acceptLicense(int $workspaceId): void
    {
        $headers = $this->getAuthHeaders();
        $headers['Content-Type'] = 'application/json-patch+json';
        $url = sprintf('%ssubmission/workspaceitems/%d', $this->host, $workspaceId);
        $patchBody = array(array('op' => 'add', 'path' => '/sections/license/granted', 'value' => 'true'));
        $this->httpGetter->patch($url, array('headers' => $headers, 'json' => $patchBody));
    }

    // 4. update item's metadata
    private function updateMetadata(int $workspaceId, array $metadata): void
    {
        $patchBody = $this->buildMetadataPatch($metadata);
        if (empty($patchBody)) {
            throw new ImproperActionException('No valid metadata fields to update.');
        }
        $headers = $this->getAuthHeaders();
        $headers['Content-Type'] = 'application/json-patch+json';
        $url = sprintf('%ssubmission/workspaceitems/%d', $this->host, $workspaceId);
        $bodyJson = json_encode($patchBody, JSON_THROW_ON_ERROR);
        $this->httpGetter->patch($url, array('headers' => $headers, 'body' => $bodyJson));
    }

    // 5. Send eLabFTW entry's .eln to DSpace as an upload (bitstream)
    private function uploadEntryAsFile(int $workspaceId, array $entity): void
    {
        $fileName = sprintf('export-elabftw-%s.eln', date('Y-m-d_H-i-s'));
        $storage = Storage::EXPORTS->getStorage();
        $absolutePath = $storage->getAbsoluteUri($fileName);
        $Entity = EntityType::from($entity['type'])->toInstance($this->requester, $entity['id']);
        $maker = new MakeEln(new ZipStream(sendHttpHeaders: false), $this->requester, $storage, array($Entity));
        $maker->writeToFile($absolutePath);
        $headers = $this->getAuthHeaders();
        $url = sprintf('%ssubmission/workspaceitems/%d', $this->host, $workspaceId);
        $this->httpGetter->post($url, array(
            'headers' => $headers,
            'multipart' => array(
                array(
                    'name' => 'file',
                    'contents' => fopen($absolutePath, 'rb'),
                    'filename' => $fileName,
                    'headers' => array('Content-Type' => 'application/zip'),
                ),
            ),
        ));
    }

    private function buildMetadataPatch(array $metadata): array
    {
        $patch = array();
        foreach ($metadata as $item) {
            $key   = $item['key']   ?? null;
            $value = $item['value'] ?? null;
            if ($key === null || $value === null || $value === '') {
                continue;
            }
            if (!isset(self::METADATA_SECTIONS[$key])) {
                continue;
            }
            $patch[] = array(
                'op'    => 'add',
                'path'  => sprintf('/sections/%s/%s', self::METADATA_SECTIONS[$key], $key),
                'value' => array(
                    array('value' => $value, 'language' => null),
                ),
            );
        }
        return $patch;
    }
}
