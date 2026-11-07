<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Auth;

use Codeception\Attribute\DataProvider;
use Elabftw\Exceptions\ForbiddenException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

use function json_encode;

final class ImpersonationGuardTest extends TestCase
{
    private const string API = '/app/controllers/ApiController.php';

    #[DataProvider('provideForbiddenRequests')]
    public function testForbiddenRequests(Request $Request): void
    {
        $this->expectException(ForbiddenException::class);
        new ImpersonationGuard()->assertAllowed($Request);
    }

    public static function provideForbiddenRequests(): array
    {
        return array(
            'create api key' => array(self::getJsonRequest(Request::METHOD_POST, '/api/v2/apikeys', array('name' => 'persist', 'canwrite' => 1))),
            'create sig keys' => array(self::getJsonRequest(Request::METHOD_POST, '/api/v2/users/me/sig_keys', array('passphrase' => 'some passphrase'))),
            'change email' => array(self::getJsonRequest(Request::METHOD_PATCH, '/api/v2/users/me', array('email' => 'evil@example.org'))),
            'change preferences' => array(self::getJsonRequest(Request::METHOD_PATCH, '/api/v2/users/me', array('limit_nb' => 15))),
            'acknowledge notification' => array(self::getJsonRequest(Request::METHOD_PATCH, '/api/v2/users/me/notifications/1', array('is_ack' => 1))),
            'create an experiment' => array(self::getJsonRequest(Request::METHOD_POST, '/api/v2/experiments')),
            'edit an experiment' => array(self::getJsonRequest(Request::METHOD_PATCH, '/api/v2/experiments/1', array('title' => 'new title'))),
            'delete an experiment' => array(self::getJsonRequest(Request::METHOD_DELETE, '/api/v2/experiments/1')),
            'create an export' => array(self::getJsonRequest(Request::METHOD_POST, '/api/v2/exports', array('format' => 'pdf'))),
            'reorder' => array(self::getPostRequest('/app/controllers/SortableAjaxController.php')),
            'ucp form' => array(self::getPostRequest('/app/controllers/UcpController.php')),
            'login form' => array(self::getPostRequest('/app/controllers/LoginController.php')),
            'unknown entrypoint' => array(self::getPostRequest('/app/controllers/SomeNewController.php')),
            'switch team' => array(Request::create('/login.php?switch_team=1', Request::METHOD_GET, server: array('SCRIPT_NAME' => '/login.php'))),
            'edit mode' => array(Request::create('/experiments.php?mode=edit&id=1', Request::METHOD_GET, server: array('SCRIPT_NAME' => '/experiments.php'))),
        );
    }

    #[DataProvider('provideAllowedRequests')]
    public function testAllowedRequests(Request $Request): void
    {
        $this->expectNotToPerformAssertions();
        new ImpersonationGuard()->assertAllowed($Request);
    }

    public static function provideAllowedRequests(): array
    {
        return array(
            'read api keys' => array(Request::create('/api/v2/apikeys', Request::METHOD_GET, server: array('SCRIPT_NAME' => self::API))),
            'read an experiment' => array(Request::create('/api/v2/experiments/1', Request::METHOD_GET, server: array('SCRIPT_NAME' => self::API))),
            'view mode' => array(Request::create('/experiments.php?mode=view&id=1', Request::METHOD_GET, server: array('SCRIPT_NAME' => '/experiments.php'))),
            'browse a page' => array(Request::create('/database.php', Request::METHOD_GET, server: array('SCRIPT_NAME' => '/database.php'))),
            'stop impersonation' => array(self::getPostRequest('/app/controllers/ImpersonationController.php', array('action' => 'stop'))),
            'logout' => array(Request::create('/app/logout.php', Request::METHOD_GET, server: array('SCRIPT_NAME' => '/app/logout.php'))),
        );
    }

    private static function getJsonRequest(string $method, string $uri, array $body = array()): Request
    {
        return Request::create(
            $uri,
            $method,
            server: array('CONTENT_TYPE' => 'application/json', 'SCRIPT_NAME' => self::API),
            content: json_encode($body, JSON_THROW_ON_ERROR),
        );
    }

    private static function getPostRequest(string $script, array $parameters = array()): Request
    {
        return Request::create($script, Request::METHOD_POST, $parameters, server: array('SCRIPT_NAME' => $script));
    }
}
