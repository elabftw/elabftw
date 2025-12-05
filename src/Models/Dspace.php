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
use Elabftw\Controllers\MakeController;
use Elabftw\Elabftw\Env;
use Elabftw\Enums\Action;
use Elabftw\Enums\DspaceAction;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\Users\Users;
use Elabftw\Services\HttpGetter;
use Override;
use Symfony\Component\HttpFoundation\Request;
use DateTimeImmutable;

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
    private string $host;

    private ?array $dspaceHeaders = null;

    // rename host to host
    public function __construct(
        private readonly Users $requester,
        private readonly HttpGetter $httpGetter,
        string $host
    ) {
        parent::__construct();
        $this->host = rtrim($host, '/') . '/';
    }

    #[Override]
    public function getApiPath(): string
    {
        return 'api/v2/dspace';
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        // default to Listing collections
        $raw = DspaceAction::ListCollections->value;
        if ($queryParams !== null && $queryParams->getQuery()->has('dspace_action')) {
            $raw = $queryParams->getQuery()->getString('dspace_action');
        }
        $action = DspaceAction::tryFrom($raw)
            ?? throw new ImproperActionException('Unknown GET action for DSpace endpoint.');
        return match ($action) {
            DspaceAction::ListCollections => $this->listOneCollection(),
            DspaceAction::ListTypes => $this->listTypes(),
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
        $this->uploadEntryAsFile($workspaceId, $params['entity']);
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
        if ($this->dspaceHeaders === null) {
            $this->dspaceHeaders = $this->getDspaceToken();
        }
        return $this->dspaceHeaders;
    }

    private function submitToWorkflow(int $workspaceId): void
    {
        $headers = $this->getAuthHeaders();
        $this->httpGetter->post($this->host . 'workflow/workflowitems', array(
            'headers' => array_merge($headers, array('Content-Type' => 'text/uri-list')),
            'body' => sprintf('/api/submission/workspaceitems/%d', $workspaceId),
        ));
    }

    private function uploadEntryAsFile(int $workspaceId, array $query): void
    {
        $query['format'] = 'eln';
        $Request = new Request($query, array(), array('entityType' => $query['type'], 'entityId' => $query['id']));
        $MakeController = new MakeController($this->requester, $Request);
        $Response = $MakeController->getResponse();
        $elnFileName = new DateTimeImmutable()->format('Y-m-d-His') . '-export.eln';
        ob_start();
        $Response->sendContent();
        $elnContent = ob_get_clean();

        $headers = $this->getAuthHeaders();
        $url = sprintf('%ssubmission/workspaceitems/%d', $this->host, $workspaceId);
        $this->httpGetter->post($url, array(
            'headers' => $headers,
            'multipart' => array(
                array(
                    'name' => 'file',
                    'contents' => $elnContent,
                    'filename' => $elnFileName,
                    'headers'  => array('Content-Type' => 'application/zip'),
                ),
            ),
        ));
    }

    private function acceptLicense(int $workspaceId): void
    {
        $headers = $this->getAuthHeaders();
        $headers['Content-Type'] = 'application/json-patch+json';
        $url = sprintf('%ssubmission/workspaceitems/%d', $this->host, $workspaceId);
        $patchBody = array(
            array('op' => 'add', 'path' => '/sections/license/granted', 'value' => 'true'),
        );
        $this->httpGetter->patch($url, array('headers' => $headers, 'json' => $patchBody));
    }

    private function getDspaceToken(): array
    {
        $Config = Config::getConfig();
        $user = $Config->configArr['dspace_user'] ?? '';
        $encPassword = $Config->configArr['dspace_password'] ?? '';
        if ($this->host === '' || $user === '' || $encPassword === '') {
            throw new ImproperActionException('DSpace config is incomplete.');
        }
        $password = Crypto::decrypt($encPassword, Key::loadFromAsciiSafeString(Env::asString('SECRET_KEY')));
        // GET CSRF TOKEN
        $csrfRes = $this->httpGetter->getWithHeaders($this->host . 'security/csrf');
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
        // Build headers
        $headers = array('Content-Type' => 'application/x-www-form-urlencoded');
        if ($cookieHeader) {
            $headers['Cookie'] = implode('; ', $cookieHeader);
        }
        if ($xsrfToken !== '') {
            $headers['X-XSRF-TOKEN'] = $xsrfToken;
        }
        // POST: login and get auth token
        $loginRes = $this->httpGetter->post($this->host . 'authn/login', array(
            'headers' => $headers, 'form_params' => array('user' => $user, 'password' => $password),
        ));
        $auth = $loginRes->getHeaderLine('Authorization');
        if ($auth === '') {
            throw new ImproperActionException(_('DSpace login did not return an Authorization header.'));
        }
        return array(
            'Authorization' => $auth,
            'X-XSRF-TOKEN' => $xsrfToken,
            'Cookie' => 'DSPACE-XSRF-COOKIE=' . $xsrfToken,
        );
    }

    // TODO: remove when done with dev. faster to target specific collection and submit
    // https://demo.dspace.org/communities/48921ed4-84f0-4110-9d02-022f3bf2307a/search
    private function listOneCollection(): array
    {
        $res = $this->httpGetter->getWithHeaders(
            $this->host . 'core/collections/26d67c5e-1515-4d55-b979-b0a1ad66af1b',
        );
        $collection = json_decode($res['body'], true);
        return array(
            '_embedded' => array(
                'collections' => array($collection),
            ),
        );
    }

    //    private function listCollections(): array
    //    {
    //        $res = $this->httpGetter->getWithHeaders(
    //            $this->host . 'core/collections',
    //        );
    //        return json_decode($res['body'], true);
    //    }

    private function listTypes(): array
    {
        $res = $this->httpGetter->getWithHeaders(
            $this->host . 'submission/vocabularies/common_types/entries',
        );
        return json_decode($res['body'], true);
    }

    private function getItemUuid(int $workspaceId): string
    {
        $headers = $this->getAuthHeaders();
        if (!$workspaceId) {
            throw new ImproperActionException('Missing workspaceId');
        }
        $url = sprintf('%ssubmission/workspaceitems/%d/item', $this->host, $workspaceId);
        $res = $this->httpGetter->getWithHeaders($url, $headers);
        $data = json_decode($res['body'], true, 512, JSON_THROW_ON_ERROR);
        if (!isset($data['uuid'])) {
            throw new ImproperActionException('DSpace did not return an item UUID.');
        }
        return $data['uuid'];
    }

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
        return (int) $data['id'];
    }

    private function updateMetadata(int $workspaceId, array $metadata): void
    {
        $headers = $this->getAuthHeaders();
        $headers['Content-Type'] = 'application/json-patch+json';
        $url = sprintf('%ssubmission/workspaceitems/%d', $this->host, $workspaceId);
        $patchBody = array();

        foreach ($metadata as $item) {
            if (!isset($item['key'], $item['value'])) {
                continue; // skip invalid entries
            }
            $section = str_contains($item['key'], 'description.abstract') ? 'traditionalpagetwo' : 'traditionalpageone';
            $patchBody[] = array(
                'op' => 'add',
                'path' => "/sections/{$section}/" . $item['key'],
                'value' => array(
                    array('value' => $item['value'], 'language' => null),
                ),
            );
        }
        if (empty($patchBody)) {
            throw new ImproperActionException('No valid metadata fields to update.');
        }
        $this->httpGetter->patch($url, array('headers' => $headers, 'json' => $patchBody));
    }
}
