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
        $Config = Config::getConfig();
        $baseUrl = $Config->configArr['dspace_host'] ?? '';
        $user = $Config->configArr['dspace_user'] ?? '';
        $encPassword = $Config->configArr['dspace_password'] ?? '';
        if ($baseUrl === '' || $user === '' || $password === '') {
            throw new ImproperActionException('DSpace config is incomplete.');
        }
        $baseUrl = rtrim($baseUrl, '/') . '/';

        $proxy      = Env::asBool('DSPACE_USE_PROXY') ? $Config->configArr['proxy'] : '';
        $HttpGetter = new HttpGetter(new Client(), $proxy, Env::asBool('DEV_MODE'));

        $password = Crypto::decrypt(
            $encPassword,
            Key::loadFromAsciiSafeString(Env::asString('SECRET_KEY')),
        );

        // get CSRF token from DSpace
        $csrfRes = $HttpGetter->getHeaders($baseUrl . 'security/csrf');
        $xsrfToken = $csrfRes['headers']['DSPACE-XSRF-TOKEN'][0] ?? '';
        $cookies = $csrfRes['headers']['Set-Cookie'] ?? array();
        $cookieHeader = array();
        $dspaceXsrfCookie = null;

        foreach ($cookies as $cookieLine) {
            $parts = explode(';', $cookieLine);
            if (count($parts) === 0) {
                continue;
            }
            $nv = trim($parts[0]);
            // DSPACE-XSRF-COOKIE (dedupe)
            if (str_starts_with($nv, 'DSPACE-XSRF-COOKIE=')) {
                if ($dspaceXsrfCookie === null) {
                    $dspaceXsrfCookie = $nv;
                    $cookieHeader[]   = $nv;
                }
                continue;
            }
        }
        $headers = array('Content-Type' => 'application/x-www-form-urlencoded',);
        if (!empty($cookieHeader)) {
            $headers['Cookie'] = implode('; ', $cookieHeader);
        }
        if ($xsrfToken !== '') {
            $headers['X-XSRF-TOKEN'] = $xsrfToken;
        }

        $loginRes = $HttpGetter->post($baseUrl . 'authn/login', array(
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
        return array('auth' => $auth);
    }

    // We don’t use these for this model: keep them minimal and explicit.

    #[Override]
    public function readOne(): array
    {
        return $this->readAll();
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        throw new ImproperActionException('Not supported for DSpace.');
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        throw new ImproperActionException('Not supported for DSpace.');
    }

    #[Override]
    public function destroy(): bool
    {
        throw new ImproperActionException('Not supported for DSpace.');
    }
}
