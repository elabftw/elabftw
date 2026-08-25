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
use Elabftw\Models\InstanceWebhooks;
use Elabftw\Params\EntityParams;
use Elabftw\Traits\TestsUtilsTrait;
use PDO;

use function json_decode;

class WebhookEmitterTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private InstanceWebhooks $Webhooks;

    private int $webhookId;

    protected function setUp(): void
    {
        $this->Webhooks = new InstanceWebhooks(true);
        $this->webhookId = $this->Webhooks->postAction(Action::Create, array(
            'name' => 'emitter test',
            'url' => 'https://192.0.2.20/hook',
            'events' => array(
                WebhookEvent::ExperimentCreated->value,
                WebhookEvent::ExperimentUpdated->value,
            ),
        ));
        // the dedup cache is per request, and a test process is one long request
        WebhookEmitter::reset();
    }

    protected function tearDown(): void
    {
        // queue rows go with it, the foreign key cascades
        new InstanceWebhooks(true, $this->webhookId)->destroy();
    }

    public function testCreatingAnExperimentQueuesAnEvent(): void
    {
        $Experiment = $this->getFreshExperiment();
        $queued = $this->getQueued(WebhookEvent::ExperimentCreated);
        $this->assertCount(1, $queued);

        $body = json_decode($queued[0]['body'], true);
        $this->assertEquals(WebhookEvent::ExperimentCreated->value, $body['event']);
        $this->assertEquals($Experiment->id, $body['id']);
        $this->assertNotEmpty($body['event_id']);
        $this->assertNotEmpty($body['at']);
        $this->assertStringEndsWith('/api/v2/experiments/' . $Experiment->id, $body['url']);
        // no api key was used, this is a plain call
        $this->assertNull($body['actor']['apikey_id']);
        $this->assertNotNull($body['actor']['userid']);
        // the payload must not carry the entity itself
        $this->assertArrayNotHasKey('title', $body);
        $this->assertArrayNotHasKey('body', $body);
    }

    public function testUpdatingAnExperimentQueuesOneEventOnly(): void
    {
        $Experiment = $this->getFreshExperiment();
        WebhookEmitter::reset();

        $Experiment->update(new EntityParams('title', 'a new title'));
        $Experiment->update(new EntityParams('rating', '3'));

        // two writes, two changelog rows, but one change as far as a subscriber cares
        $this->assertCount(1, $this->getQueued(WebhookEvent::ExperimentUpdated));
    }

    public function testUnsubscribedEventIsNotQueued(): void
    {
        // this webhook only listens for experiments
        $this->getFreshItem();
        $this->assertCount(0, $this->getQueued(WebhookEvent::ItemCreated));
    }

    public function testDisabledWebhookGetsNothing(): void
    {
        new InstanceWebhooks(true, $this->webhookId)->patch(Action::Update, array('enabled' => 0));
        WebhookEmitter::reset();

        $this->getFreshExperiment();
        $this->assertCount(0, $this->getQueued(WebhookEvent::ExperimentCreated));
    }

    /**
     * A status change is its own event, for experiments and resources alike: reacting to
     * one is the whole point for a subscriber, and it should not have to diff an update
     * against the previous state to notice.
     */
    public function testStatusChangeIsItsOwnEvent(): void
    {
        $probeId = new InstanceWebhooks(true)->postAction(Action::Create, array(
            'name' => 'status probe',
            'url' => 'https://192.0.2.21/hook',
            'events' => array(
                WebhookEvent::ExperimentStatusChanged->value,
                WebhookEvent::ItemStatusChanged->value,
            ),
        ));
        WebhookEmitter::reset();

        $Experiment = $this->getFreshExperiment();
        $Experiment->update(new EntityParams('status', (string) $this->getStatusId('experiments_status')));
        $Item = $this->getFreshItem();
        $Item->update(new EntityParams('status', (string) $this->getStatusId('items_status')));

        $events = $this->getEventsFor($probeId);
        $this->assertContains(WebhookEvent::ExperimentStatusChanged->value, $events);
        $this->assertContains(WebhookEvent::ItemStatusChanged->value, $events);

        new InstanceWebhooks(true, $probeId)->destroy();
    }

    private function getStatusId(string $table): int
    {
        $Db = Db::getConnection();
        // the table name is a literal from the caller, never user input
        $req = $Db->prepare('SELECT id FROM ' . $table . ' WHERE team = 1 ORDER BY id ASC LIMIT 1');
        $Db->execute($req);
        return (int) $Db->fetch($req)['id'];
    }

    /**
     * @return array<int, string>
     */
    private function getEventsFor(int $webhookId): array
    {
        $Db = Db::getConnection();
        $req = $Db->prepare('SELECT event FROM webhooks_queue WHERE webhooks_id = :id');
        $req->bindValue(':id', $webhookId, PDO::PARAM_INT);
        $Db->execute($req);
        return $req->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getQueued(WebhookEvent $event): array
    {
        $Db = Db::getConnection();
        $sql = 'SELECT event, event_id, body FROM webhooks_queue WHERE webhooks_id = :id AND event = :event';
        $req = $Db->prepare($sql);
        $req->bindValue(':id', $this->webhookId, PDO::PARAM_INT);
        $req->bindValue(':event', $event->value);
        $Db->execute($req);
        return $req->fetchAll();
    }
}
