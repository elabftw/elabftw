<?php

declare(strict_types=1);

/**
 * @author Moritz IHLER
 * @copyright 2026 Moritz IHLER
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\WebhookEvent;
use Elabftw\Enums\WebhookState;
use Elabftw\Models\InstanceWebhooks;
use Elabftw\Traits\TestsUtilsTrait;
use GuzzleHttp\Psr7\Response;
use PDO;
use Symfony\Component\Console\Output\NullOutput;

use function hash_hmac;

class WebhookDispatcherTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private int $webhookId;

    private string $secret;

    /** @var array<int, array{0: string, 1: array}> */
    private array $captured = array();

    protected function setUp(): void
    {
        $this->captured = array();
        $this->webhookId = new InstanceWebhooks(true)->postAction(Action::Create, array(
            'name' => 'dispatcher test',
            'url' => 'https://192.0.2.30/hook',
            'events' => array(WebhookEvent::ExperimentCreated->value),
        ));
        $this->secret = new InstanceWebhooks(true, $this->webhookId)->readOne()['secret'];
        WebhookEmitter::reset();
        $this->getFreshExperiment();
    }

    protected function tearDown(): void
    {
        new InstanceWebhooks(true, $this->webhookId)->destroy();
    }

    public function testSuccessfulDeliveryIsMarkedDelivered(): void
    {
        $this->send(new Response(200, array(), '{"ok":true}'));

        $row = $this->getRow();
        $this->assertEquals(WebhookState::Delivered->value, (int) $row['state']);
        $this->assertNotNull($row['delivered_at']);
        $this->assertNull($row['claim_token']);
    }

    public function testBodyIsSignedWithTheWebhookSecret(): void
    {
        $this->send(new Response(200, array(), '{"ok":true}'));

        $this->assertCount(1, $this->captured);
        [$url, $options] = $this->captured[0];
        $this->assertEquals('https://192.0.2.30/hook', $url);

        $expected = 'sha256=' . hash_hmac('sha256', $options['body'], $this->secret);
        $this->assertEquals($expected, $options['headers'][WebhookDispatcher::SIGNATURE_HEADER]);
        $this->assertEquals(WebhookEvent::ExperimentCreated->value, $options['headers'][WebhookDispatcher::EVENT_HEADER]);
        $this->assertNotEmpty($options['headers'][WebhookDispatcher::DELIVERY_HEADER]);
        // a redirect would lead to a host that never passed the address checks
        $this->assertFalse($options['allow_redirects']);
    }

    public function testFailedDeliveryIsRequeuedForLater(): void
    {
        $this->send(new Response(500, array(), 'nope'));

        $row = $this->getRow();
        $this->assertEquals(WebhookState::Queued->value, (int) $row['state']);
        $this->assertEquals(1, (int) $row['attempts']);
        $this->assertNull($row['claim_token']);
        $this->assertStringContainsString('500', (string) $row['last_error']);
        // it must not be picked up again immediately, or a dead target is hammered every minute
        $this->assertGreaterThan(0, (int) $row['due_in_seconds']);
    }

    public function testRequeuedRowIsNotClaimedAgainInTheSameRun(): void
    {
        $this->send(new Response(500, array(), 'nope'));
        $this->captured = array();
        $this->send(new Response(200, array(), '{"ok":true}'));

        $this->assertCount(0, $this->captured);
        $this->assertEquals(1, (int) $this->getRow()['attempts']);
    }

    private function send(Response $response): void
    {
        $getterStub = $this->createStub(HttpGetter::class);
        $getterStub->method('post')->willReturnCallback(
            function (string $url, array $options) use ($response): Response {
                $this->captured[] = array($url, $options);
                return $response;
            }
        );
        // not strict: the target is a documentation address, there is nothing to resolve
        new WebhookDispatcher($getterStub, new WebhookUrlValidator(false))->send(new NullOutput());
    }

    private function getRow(): array
    {
        $Db = Db::getConnection();
        $sql = 'SELECT state, attempts, claim_token, delivered_at, last_error,
                TIMESTAMPDIFF(SECOND, NOW(), next_attempt_at) AS due_in_seconds
            FROM webhooks_queue WHERE webhooks_id = :id';
        $req = $Db->prepare($sql);
        $req->bindValue(':id', $this->webhookId, PDO::PARAM_INT);
        $Db->execute($req);
        return $Db->fetch($req);
    }
}
