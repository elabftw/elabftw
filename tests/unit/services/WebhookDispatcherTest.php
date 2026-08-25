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
use Elabftw\Models\WebhooksQueue;
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

    /**
     * Turning a webhook off has to stop what is already queued for it, otherwise disabling
     * a misbehaving target still lets the backlog through.
     */
    public function testDisabledWebhookIsNotDelivered(): void
    {
        new InstanceWebhooks(true, $this->webhookId)->patch(Action::Update, array('enabled' => 0));

        $this->send(new Response(200, array(), '{"ok":true}'));

        $this->assertCount(0, $this->captured);
        $this->assertEquals(WebhookState::Queued->value, (int) $this->getRow()['state']);
    }

    /**
     * A drain that hangs past the stale window loses its row to the next one. When it
     * finally returns it must not overwrite the newer result, or a delivered row could be
     * put back in the queue and sent twice.
     */
    public function testLostClaimCannotOverwriteTheNewerOne(): void
    {
        $Queue = new WebhooksQueue();
        $first = $this->claimOurs($Queue);
        $this->assertNotNull($first);
        $staleToken = (string) $first['claim_token'];

        // pretend the claim has been sitting there longer than the stale window
        $Db = Db::getConnection();
        $req = $Db->prepare('UPDATE webhooks_queue SET next_attempt_at = DATE_SUB(NOW(), INTERVAL 11 MINUTE) WHERE id = :id');
        $req->bindValue(':id', (int) $first['id'], PDO::PARAM_INT);
        $Db->execute($req);

        $Queue->releaseStaleClaims();
        $second = $this->claimOurs($Queue);
        $this->assertNotNull($second);
        $this->assertNotEquals($staleToken, (string) $second['claim_token']);

        // the first drain returns from the dead and tries to finish its work
        $this->assertFalse($Queue->markDelivered((int) $first['id'], $staleToken));
        $this->assertFalse($Queue->markFailed((int) $first['id'], $staleToken, 'too late'));
        $this->assertFalse($Queue->markRetry((int) $first['id'], $staleToken, 'too late', 60));

        // the row still belongs to the second drain
        $this->assertEquals(WebhookState::Sending->value, (int) $this->getRow()['state']);
    }

    private function claimOurs(WebhooksQueue $Queue): ?array
    {
        foreach ($Queue->claim(20) as $row) {
            if ((int) $row['webhook_id'] === $this->webhookId) {
                return $row;
            }
        }
        return null;
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
