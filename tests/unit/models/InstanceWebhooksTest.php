<?php

declare(strict_types=1);

/**
 * @author Moritz IHLER
 * @copyright 2026 Moritz IHLER
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Enums\WebhookEvent;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;

use function count;

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

    public function testSecretIsHiddenWithoutWriteAccess(): void
    {
        $id = $this->InstanceWebhooks->postAction(Action::Create, array(
            'url' => 'https://192.0.2.11/hook',
            'events' => array(WebhookEvent::ItemCreated->value),
        ));
        $webhook = new InstanceWebhooks(false, $id)->readOne();
        $this->assertArrayNotHasKey('secret', $webhook);
        new InstanceWebhooks(true, $id)->destroy();
    }

    public function testNotSysadmin(): void
    {
        $this->expectException(IllegalActionException::class);
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
