<?php

declare(strict_types=1);

/**
 * @author Moritz IHLER
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\WebhookEvent;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use PDO;

use function count;
use function sprintf;

class InstanceWebhooksTest extends \PHPUnit\Framework\TestCase
{
    private InstanceWebhooks $InstanceWebhooks;

    protected function setUp(): void
    {
        $this->InstanceWebhooks = new InstanceWebhooks(true);
    }

    public function testGetApiPath(): void
    {
        $this->assertStringEndsWith('webhooks/', $this->InstanceWebhooks->getApiPath());
    }

    public function testCreateReadUpdateDestroy(): void
    {
        $initialCount = count($this->InstanceWebhooks->readAll());
        $id = $this->InstanceWebhooks->postAction(Action::Create, array(
            'name' => 'test webhook',
            'url' => 'https://192.0.2.10/hook',
            'events' => array(WebhookEvent::ExperimentUpdated->value),
        ));
        $this->assertEquals($initialCount + 1, count($this->InstanceWebhooks->readAll()));

        $Webhook = new InstanceWebhooks(true, $id);
        $webhook = $Webhook->readOne();
        $this->assertEquals('https://192.0.2.10/hook', $webhook['url']);
        $this->assertEquals(1, $webhook['enabled']);
        // the secret is readable by someone who may write the webhook, they need it to verify
        $this->assertNotEmpty($webhook['secret']);

        $updated = $Webhook->patch(Action::Update, array('enabled' => 0));
        $this->assertEquals(0, $updated['enabled']);

        $this->assertTrue($Webhook->destroy());
        $this->assertEquals($initialCount, count($this->InstanceWebhooks->readAll()));
    }

    /**
     * A caller sends events as a list, so it gets a list back: the json in the column is an
     * implementation detail, and a string here would force every client to parse it.
     */
    public function testEventsAreReadBackAsAList(): void
    {
        $events = array(WebhookEvent::ExperimentCreated->value, WebhookEvent::ItemStatusChanged->value);
        $id = $this->InstanceWebhooks->postAction(Action::Create, array(
            'url' => 'https://192.0.2.16/hook',
            'events' => $events,
        ));
        $Webhook = new InstanceWebhooks(true, $id);
        $this->assertEquals($events, $Webhook->readOne()['events']);

        $listed = null;
        foreach ($this->InstanceWebhooks->readAll() as $webhook) {
            if ((int) $webhook['id'] === $id) {
                $listed = $webhook;
            }
        }
        $this->assertNotNull($listed);
        $this->assertEquals($events, $listed['events']);

        // also after a patch, which answers with the webhook it just changed
        $patched = $Webhook->patch(Action::Update, array('events' => array(WebhookEvent::ItemCreated->value)));
        $this->assertEquals(array(WebhookEvent::ItemCreated->value), $patched['events']);

        $Webhook->destroy();
    }

    /**
     * The secret is encrypted with SECRET_KEY in the database, and handed back in clear
     * only through readOne().
     */
    public function testSecretIsEncryptedAtRest(): void
    {
        $id = $this->InstanceWebhooks->postAction(Action::Create, array(
            'url' => 'https://192.0.2.12/hook',
            'events' => array(WebhookEvent::ItemUpdated->value),
        ));
        $Webhook = new InstanceWebhooks(true, $id);
        $secret = $Webhook->readOne()['secret'];
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $secret);

        $Db = Db::getConnection();
        $req = $Db->prepare('SELECT secret FROM webhooks WHERE id = :id');
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $Db->execute($req);
        $stored = (string) $req->fetchColumn();
        $this->assertNotSame($secret, $stored);
        $this->assertStringNotContainsString($secret, $stored);
        // defuse ciphertext starts with its version header
        $this->assertStringStartsWith('def', $stored);

        $Webhook->destroy();
    }

    /**
     * A webhook target is an outbound data flow, so its url, its events and its failure
     * history are not public: reading takes the same authority as writing.
     */
    public function testReadingWithoutPermissionIsRefused(): void
    {
        $id = $this->InstanceWebhooks->postAction(Action::Create, array(
            'url' => 'https://192.0.2.11/hook',
            'events' => array(WebhookEvent::ItemCreated->value),
        ));
        try {
            new InstanceWebhooks(false, $id)->readOne();
            $this->fail('reading a webhook without permission should have been refused');
        } catch (ForbiddenException) {
            $this->addToAssertionCount(1);
        }
        try {
            new InstanceWebhooks(false)->readAll();
            $this->fail('listing webhooks without permission should have been refused');
        } catch (ForbiddenException) {
            $this->addToAssertionCount(1);
        }
        new InstanceWebhooks(true, $id)->destroy();
    }

    public function testNotSysadmin(): void
    {
        $this->expectException(ForbiddenException::class);
        new InstanceWebhooks(false)->postAction(Action::Create, array(
            'url' => 'https://192.0.2.12/hook',
            'events' => array(WebhookEvent::ExperimentCreated->value),
        ));
    }

    public function testCreateWithoutEvents(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->InstanceWebhooks->postAction(Action::Create, array(
            'url' => 'https://192.0.2.13/hook',
            'events' => array(),
        ));
    }

    public function testCreateWithUnknownEvent(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->InstanceWebhooks->postAction(Action::Create, array(
            'url' => 'https://192.0.2.14/hook',
            'events' => array('experiment.exploded'),
        ));
    }

    /**
     * Every event is a delivery per webhook, drained one after another inside a fixed time
     * budget, so the number of them cannot be left open.
     */
    public function testCreateBeyondTheLimitIsRefused(): void
    {
        // the number openapi.yaml promises, spelled out rather than read from the model: it is
        // part of the documented contract, so moving it should make a test fail
        $limit = 10;
        $created = array();
        try {
            // one more than the limit, however many the shared scope already holds
            for ($i = 0; $i <= $limit; $i++) {
                $created[] = $this->InstanceWebhooks->postAction(Action::Create, array(
                    'url' => sprintf('https://192.0.2.%d/hook', 100 + $i),
                    'events' => array(WebhookEvent::ExperimentCreated->value),
                ));
            }
            $this->fail('creating more webhooks than the limit should have been refused');
        } catch (ImproperActionException) {
            $this->assertCount($limit, $this->InstanceWebhooks->readAll());
        } finally {
            foreach ($created as $id) {
                new InstanceWebhooks(true, $id)->destroy();
            }
        }
    }

    public function testUpdateWithUnknownParameter(): void
    {
        $id = $this->InstanceWebhooks->postAction(Action::Create, array(
            'url' => 'https://192.0.2.15/hook',
            'events' => array(WebhookEvent::ExperimentUpdated->value),
        ));
        $Webhook = new InstanceWebhooks(true, $id);
        try {
            $Webhook->patch(Action::Update, array('secret' => 'let me pick my own'));
            $this->fail('an unknown parameter should have been refused');
        } catch (ImproperActionException) {
            $this->addToAssertionCount(1);
        }
        $Webhook->destroy();
    }
}
