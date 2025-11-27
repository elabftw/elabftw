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

//use Defuse\Crypto\Crypto;
//use Defuse\Crypto\Key;
//use Elabftw\Elabftw\Env;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
//use Elabftw\Services\HttpGetter;
use GuzzleHttp\Client;
use Override;

/**
 * Connect with DSpace Repository
 * https://dspace.org/
 */
final class Dspace extends AbstractRest
{
    private const string DSPACE_URL = 'https://demo.dspace.org/server/api/';

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

        $host = rtrim($Config->configArr['dspace_host'] ?? '', '/');
        //        $user = $Config->configArr['dspace_user'] ?? '';
        //        $encPassword = $Config->configArr['dspace_password'] ?? '';

        // temporarily try with demo because local instance = docker network/proxy/location issues
        $user = 'dspacedemo+admin@gmail.com';
        $password = 'dspace';
        //        if ($host === '' || $user === '' || $encPassword === '') {
        //            throw new ImproperActionException('DSpace config is incomplete.');
        //        }

        //        $password = Crypto::decrypt(
        //            $encPassword,
        //            Key::loadFromAsciiSafeString(Env::asString('SECRET_KEY')),
        //        );

        // IMPORTANT: dspace_host must point to the DSpace REST base, e.g.
        // https://demo.dspace.org/server/api/

        //        $HttpGetter = new HttpGetter(new Client(array(
        //            'base_uri' => $host,
        //            'verify' => !Env::asBool('DEV_MODE'),
        //        )), $Config->configArr['proxy']);
        $client = new Client(array(
            'base_uri' => $host,
            'verify' => false,
        ));

        // get CSRF token from DSpace
        $csrfRes = $client->get(self::DSPACE_URL . 'security/csrf');
        $xsrfToken = $csrfRes->getHeaderLine('dspace-xsrf-token');
        $headers = array('Content-Type' => 'application/x-www-form-urlencoded',);
        if ($xsrfToken !== '') {
            $headers['X-XSRF-TOKEN'] = $xsrfToken;
            // some DSpace setups require the cookie too:
            $headers['Cookie'] = 'DSPACE-XSRF-COOKIE=' . $xsrfToken;
        }

        $loginRes = $client->post(self::DSPACE_URL . 'authn/login', array(
            'headers' => $headers,
            'form_params' => array('user' => $user, 'password' => $password),
        ));

        if ($loginRes->getStatusCode() < 200 || $loginRes->getStatusCode() >= 300) {
            $body = (string) $loginRes->getBody();
            throw new ImproperActionException(sprintf(_('DSpace login failed: %s - %s'), $loginRes->getStatusCode(), $body));
        }

        // Now Dspace returns an Authorization header we can re-use from JS
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
