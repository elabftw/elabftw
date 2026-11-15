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
use Elabftw\Enums\Action;
use Elabftw\Enums\WebhookEvent;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Models\UsersWebhooks;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

use function array_merge;
use function json_encode;
use function sprintf;

final class ImpersonationGuardTest extends TestCase
{
    use TestsUtilsTrait;

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
            'method override on a controller' => array(Request::create(
                '/app/controllers/UcpController.php',
                Request::METHOD_POST,
                array('mfa_secret' => 'JBSWY3DPEHPK3PXP', 'mfa_code' => '123456'),
                server: array('SCRIPT_NAME' => '/app/controllers/UcpController.php', 'HTTP_X_HTTP_METHOD_OVERRIDE' => 'GET'),
            )),
            'method override on the api' => array(self::getJsonRequest(Request::METHOD_POST, '/api/v2/users/me', array('limit_nb' => 15), array('HTTP_X_HTTP_METHOD_OVERRIDE' => 'GET'))),
            'get with a body on a controller' => array(Request::create(
                '/app/controllers/SortableAjaxController.php',
                Request::METHOD_GET,
                server: array('SCRIPT_NAME' => '/app/controllers/SortableAjaxController.php', 'CONTENT_TYPE' => 'application/json'),
                content: json_encode(array('table' => 'todolist', 'ordering' => array('todoItem_1')), JSON_THROW_ON_ERROR),
            )),
            'options with a body on a controller' => array(Request::create(
                '/app/controllers/SortableAjaxController.php',
                Request::METHOD_OPTIONS,
                server: array('SCRIPT_NAME' => '/app/controllers/SortableAjaxController.php', 'CONTENT_TYPE' => 'application/json'),
                content: json_encode(array('table' => 'todolist', 'ordering' => array('todoItem_1')), JSON_THROW_ON_ERROR),
            )),
            'get on a controller' => array(Request::create('/app/controllers/UcpController.php', Request::METHOD_GET, server: array('SCRIPT_NAME' => '/app/controllers/UcpController.php'))),
            'options on the api' => array(Request::create('/api/v2/experiments', Request::METHOD_OPTIONS, server: array('SCRIPT_NAME' => self::API))),
            'edit mode with an extra character' => array(Request::create('/experiments.php?mode=edit_&id=1', Request::METHOD_GET, server: array('SCRIPT_NAME' => '/experiments.php'))),
            'edit mode with a digit' => array(Request::create('/experiments.php?mode=ed1it&id=1', Request::METHOD_GET, server: array('SCRIPT_NAME' => '/experiments.php'))),
            'switch team' => array(Request::create('/login.php?switch_team=1', Request::METHOD_GET, server: array('SCRIPT_NAME' => '/login.php'))),
            'read a user webhook' => array(Request::create('/api/v2/users/me/webhooks/1', Request::METHOD_GET, server: array('SCRIPT_NAME' => self::API))),
            'read a team webhook' => array(Request::create('/api/v2/teams/current/webhooks/1', Request::METHOD_GET, server: array('SCRIPT_NAME' => self::API))),
            'read an instance webhook' => array(Request::create('/api/v2/instance/webhooks/1', Request::METHOD_GET, server: array('SCRIPT_NAME' => self::API))),
            'read signature keys' => array(Request::create('/api/v2/users/me/sig_keys', Request::METHOD_GET, server: array('SCRIPT_NAME' => self::API))),
            'read one signature key' => array(Request::create('/api/v2/users/me/sig_keys/1', Request::METHOD_GET, server: array('SCRIPT_NAME' => self::API))),
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
            'list webhooks' => array(Request::create('/api/v2/users/me/webhooks', Request::METHOD_GET, server: array('SCRIPT_NAME' => self::API))),
            'list webhooks with trailing slash' => array(Request::create('/api/v2/users/me/webhooks/', Request::METHOD_GET, server: array('SCRIPT_NAME' => self::API))),
            'read an experiment' => array(Request::create('/api/v2/experiments/1', Request::METHOD_GET, server: array('SCRIPT_NAME' => self::API))),
            'view mode' => array(Request::create('/experiments.php?mode=view&id=1', Request::METHOD_GET, server: array('SCRIPT_NAME' => '/experiments.php'))),
            'browse a page' => array(Request::create('/database.php', Request::METHOD_GET, server: array('SCRIPT_NAME' => '/database.php'))),
            'stop impersonation' => array(self::getPostRequest('/app/controllers/ImpersonationController.php', array('action' => 'stop'))),
            'logout' => array(Request::create('/app/logout.php', Request::METHOD_GET, server: array('SCRIPT_NAME' => '/app/logout.php'))),
            'change language' => array(Request::create('/app/controllers/UnauthRequestHandler.php?lang=fr_FR', Request::METHOD_GET, server: array('SCRIPT_NAME' => '/app/controllers/UnauthRequestHandler.php'))),
            'download a file' => array(Request::create('/app/download.php?f=ab/abc.png&storage=1', Request::METHOD_GET, server: array('SCRIPT_NAME' => '/app/download.php'))),
            'head on a page' => array(Request::create('/experiments.php', Request::METHOD_HEAD, server: array('SCRIPT_NAME' => '/experiments.php'))),
        );
    }

    /**
     * Regression: an Admin impersonating a user must not be able to read the signing secret of their existing webhook
     */
    public function testImpersonatorCannotReadExistingWebhookSecret(): void
    {
        $userid = $this->getUserIdFromEmail('user1@demo.elabftw.net');
        $Webhooks = UsersWebhooks::forRequester(new Users($userid), $userid);
        $id = $Webhooks->postAction(Action::Create, array(
            'name' => 'existing hook',
            'url' => 'https://192.0.2.41/hook',
            'events' => array(WebhookEvent::ExperimentUpdated->value),
        ));
        try {
            // the secret is there for the real user
            $this->assertNotEmpty(UsersWebhooks::forRequester(new Users($userid), $userid, $id)->readOne()['secret']);
            // the impersonator can list the webhooks, which carry no secret
            new ImpersonationGuard()->assertAllowed(Request::create('/api/v2/users/me/webhooks', Request::METHOD_GET, server: array('SCRIPT_NAME' => self::API)));
            foreach ($Webhooks->readAll() as $webhook) {
                $this->assertArrayNotHasKey('secret', $webhook);
            }
            // but not read the one that does
            foreach (array('/api/v2/users/me/webhooks/' . $id, '/api/v2/users/' . $userid . '/webhooks/' . $id) as $uri) {
                try {
                    new ImpersonationGuard()->assertAllowed(Request::create($uri, Request::METHOD_GET, server: array('SCRIPT_NAME' => self::API)));
                    $this->fail(sprintf('Webhook secret readable while impersonating with %s', $uri));
                } catch (ForbiddenException) {
                    // expected
                }
            }
        } finally {
            UsersWebhooks::forRequester(new Users($userid), $userid, $id)->destroy();
        }
    }

    public function testImpersonatorCannotReadSignaturePrivateKey(): void
    {
        $user = new Users($this->getUserIdFromEmail('user1@demo.elabftw.net'));
        $this->assertArrayHasKey('sig_privkey', $user->readOne());
        $user->impersonatorId = 1;
        $this->assertArrayNotHasKey('sig_privkey', $user->readOne());
    }

    #[DataProvider('provideCanWrite')]
    public function testCanWrite(string $script, bool $expected): void
    {
        $this->assertSame($expected, new ImpersonationGuard()->canWrite(Request::create($script, server: array('SCRIPT_NAME' => $script))));
    }

    public static function provideCanWrite(): array
    {
        return array(
            'stop impersonation' => array('/app/controllers/ImpersonationController.php', true),
            'logout' => array('/app/logout.php', true),
            'api' => array(self::API, false),
            'page' => array('/experiments.php', false),
            'download' => array('/app/download.php', false),
        );
    }

    private static function getJsonRequest(string $method, string $uri, array $body = array(), array $server = array()): Request
    {
        return Request::create(
            $uri,
            $method,
            server: array_merge(array('CONTENT_TYPE' => 'application/json', 'SCRIPT_NAME' => self::API), $server),
            content: json_encode($body, JSON_THROW_ON_ERROR),
        );
    }

    private static function getPostRequest(string $script, array $parameters = array()): Request
    {
        return Request::create($script, Request::METHOD_POST, $parameters, server: array('SCRIPT_NAME' => $script));
    }
}
