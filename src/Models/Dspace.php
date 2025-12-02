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

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        return $this->createWorkspaceItem($reqBody);
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        $workspaceId = $this->postAction($action, $params);
        $uuid = $this->getItemUuid($workspaceId);
        $this->acceptLicense($workspaceId);
        $this->updateMetadata($workspaceId, $params['metadata'] ?? array());

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

    private function acceptLicense(int $workspaceId): void
    {
        $headers = $this->getDspaceToken();
        $headers['Content-Type'] = 'application/json-patch+json';
        $url = $this->baseUrl . 'submission/workspaceitems/' . $workspaceId;
        $patchBody = array(
            array(
                'op' => 'add',
                'path' => '/sections/license/granted',
                'value' => 'true',
            ),
        );
        $this->HttpGetter->patch($url, array(
            'headers' => $headers,
            'json' => $patchBody,
        ));
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

    private function getItemUuid(int $workspaceId): string
    {
        $headers = $this->getDspaceToken();
        if (!$workspaceId) {
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
        return $data['uuid'];
    }

    /*
     * POST METHODS
     * exportItem, createItem, acceptLicense, updateMetadata, uploadEntryAsFile, submitItem
     */
    private function createWorkspaceItem(array $reqBody): int
    {
        $collection = $reqBody['collection'] ?? '';
        $metadata = $reqBody['metadata'] ?? array();

        if ($collection === '' || empty($metadata)) {
            throw new ImproperActionException('Missing required export fields.');
        }
        $headers = $this->getDspaceToken();
        $headers['Content-Type'] = 'application/json';
        $url = $this->baseUrl . 'submission/workspaceitems?owningCollection=' . $collection;

        $res = $this->HttpGetter->post($url, array('headers' => $headers, 'json' => $metadata));
        $body = $res->getBody()->getContents();
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        return (int) $data['id'];
        //        // - Update metadata
        //        $this->updateMetadata($workspaceId, $metadata, $headers);
        //
        //        // - Upload ELN file (to be implemented)
        //        $this->uploadEntryAsFile($workspaceId, $headers);
        //
        //        // - Submit to workflow
        //        $this->submitItem($workspaceId, $headers);
    }

    private function updateMetadata(int $workspaceId, array $metadata): void
    {
        $headers = $this->getDspaceToken();
        $headers['Content-Type'] = 'application/json-patch+json';

        $url = $this->baseUrl . 'submission/workspaceitems/' . $workspaceId;
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
        $this->HttpGetter->patch($url, array(
            'headers' => $headers,
            'json'    => $patchBody,
        ));
    }
}
