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
     * We abuse readAll() as a "login to DSpace and give me an auth token" endpoint.
     * Called by GET /api/v2/dspace from the JS.
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
            'itemUuid' => $this->getItemUuid($queryParams ?? throw new ImproperActionException('Missing query params')),
            default => throw new ImproperActionException('Unknown DSpace GET action.'),
        };
    }

    public function getDspaceToken(): array
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

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        if ($action !== Action::Create) {
            throw new ImproperActionException('Only update action is supported here.');
        }
        return match ($action) {
            Action::Create => $this->createItem($reqBody),
            default => throw new ImproperActionException('Unsupported DSpace postAction.'),
        };
        //        return match ($reqBody['dspace_action'] ?? '') {
        //            'acceptLicense' => $this->acceptLicense($reqBody),
        //            'updateMetadata' => $this->updateMetadata($reqBody),
        //            'uploadFile' => $this->uploadFile($reqBody),
        //            'submitToWorkflow' => $this->submitToWorkflow($reqBody),
        //            default => throw new ImproperActionException('Unknown DSpace sub-action.'),
        //        };
    }

    public function createItem(array $reqBody): int
    {
        $collection = $reqBody['collection'] ?? '';
        $metadata = $reqBody['metadata'] ?? [];
        $incomingHeaders = $reqBody['headers'] ?? [];

        $headers = array(
            'Authorization' => $incomingHeaders['Authorization'] ?? '',
            'X-XSRF-TOKEN'  => $incomingHeaders['X-XSRF-TOKEN'] ?? '',
            'Content-Type'  => 'application/json',
        );

        if (!empty($incomingHeaders['Cookie'])) {
            $headers['Cookie'] = $incomingHeaders['Cookie'];
        }

        if ($collection === '' || empty($metadata)) {
            throw new ImproperActionException('Missing collection or metadata for workspace item creation.');
        }
        $url = $this->baseUrl . 'submission/workspaceitems?owningCollection=' . $collection;

//        dd($headers);
        $res = $this->HttpGetter->post($url, [
            'headers' => $headers,
            'json' => $metadata,
        ]);

        dd($res);
        $data = json_decode($res->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        if (!isset($data['id'])) {
            throw new ImproperActionException('DSpace response did not return a workspace item ID.');
        }

        return (int) $data['id'];
    }
//
//    public function createWorkspaceItem(array $reqBody): int
//    {
//        $collection = $reqBody['collection'] ?? '';
//        $metadata = $reqBody['metadata'] ?? [];
//        if ($collection === '' || empty($metadata)) {
//            throw new ImproperActionException('Missing collection or metadata for workspace item creation.');
//        }
//        $res = $this->HttpGetter->post(
//            $this->baseUrl . 'submission/workspaceitems?owningCollection=' . urlencode($collection),
//            [
//                'headers' => array_merge($this->getAuthHeaders(), [
//                    'Content-Type' => 'application/json',
//                ]),
//                'json' => $metadata,
//            ]
//        );
//
//        $data = json_decode((string) $res->getBody(), true);
//        if (!isset($data['id'])) {
//            throw new ImproperActionException('DSpace response missing workspace item ID.');
//        }
//
//        // return full object to frontend if needed
//        echo json_encode($data);
//        exit;
//    }
    // all GET actions for DSpace
    //    public function listCollections(?QueryParamsInterface $queryParams = null): array
    #[Override]
    public function readOne(): array
    {
        return array();
        //        return match ($_GET['action'] ?? '') {
        //            'listCollections' => $this->listCollections(),
        //            'listTypes' => $this->listTypes(),
        //            'getItemUuid' => $this->getItemUuidFromWorkspace(),
        //            default => $this->readAll(),
        //        };
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        //        if ($action !== Action::Update) {
        //            throw new ImproperActionException('Only Action::Update is supported for DSpace.');
        //        }
        //        return match ($params['dspace_action'] ?? '') {
        //            'acceptLicense' => $this->acceptLicense($params),
        //            'updateMetadata' => $this->updateMetadata($params),
        //            'uploadFile' => $this->uploadFile($params),
        //            'submitToWorkflow' => $this->submitToWorkflow($params),
        //            default => throw new ImproperActionException('Unknown DSpace sub-action.'),
        //        };
        return array();

    }

    #[Override]
    public function destroy(): bool
    {
        throw new ImproperActionException('Not supported for DSpace.');
    }

    private function getAuthHeaders(): array
    {
        $auth = $_SESSION['dspaceAuth'] ?? '';
        $xsrf = $_SESSION['dspaceXsrfToken'] ?? '';
        $headers = array();
        if ($auth) {
            $headers['Authorization'] = $auth;
        }
        if ($xsrf) {
            $headers['X-XSRF-TOKEN'] = $xsrf;
        }
        return $headers;
    }

    private function listCollections(): array
    {
        $res = $this->HttpGetter->getWithHeaders(
            $this->baseUrl . 'core/collections',
            $this->getAuthHeaders()
        );
        return json_decode($res['body'], true);
    }

    private function listTypes(): array
    {
        $res = $this->HttpGetter->getWithHeaders(
            $this->baseUrl . 'submission/vocabularies/common_types/entries',
            $this->getAuthHeaders()
        );
        return json_decode($res['body'], true);
    }

    private function getItemUuid(QueryParamsInterface $queryParams): array
    {
        $workspaceId = $queryParams->getQuery()->getString('workspaceId');
        if ($workspaceId === '') {
            throw new ImproperActionException('Missing workspaceId');
        }

        $res = $this->HttpGetter->getWithHeaders(
            $this->baseUrl . 'submission/workspaceitems/' . urlencode($workspaceId) . '/item',
            $this->getAuthHeaders()
        );
        return json_decode($res['body'], true);
    }
}
