<?php

/**
 * @author Moritz IHLER
 * @copyright 2026 Moritz IHLER
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\WebhookEvent;
use Elabftw\Enums\WebhookState;
use PDO;

use function bin2hex;
use function json_encode;
use function mb_substr;
use function random_bytes;

use const JSON_THROW_ON_ERROR;

/**
 * The webhooks_queue table: one row per delivery, so a slow or broken target only affects
 * its own rows.
 *
 * Nothing here talks HTTP. Rows are inserted during a normal request (cheap: one select and
 * one insert per subscriber) and drained out of band by the webhooks:send command.
 */
final class WebhooksQueue
{
    /** rows in Sending state older than this are considered abandoned by a crashed drain */
    public const int STALE_CLAIM_MINUTES = 10;

    /** delivered rows are kept this long, as a delivery log for the admin */
    public const int KEEP_DELIVERED_DAYS = 7;

    private Db $Db;

    public function __construct()
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Insert one row per webhook that is entitled to this event and subscribed to it.
     *
     * The entitlement rules are deliberately restrictive for a first version: an instance
     * webhook sees everything, a team webhook sees its own team, and a user webhook sees only
     * entries owned by that user. Widening this later is harmless, narrowing it would break
     * existing integrations.
     *
     * @return int number of deliveries queued
     */
    public function fanout(WebhookEvent $event, array $payload, int $teamId, int $ownerId): int
    {
        $sql = "SELECT id FROM webhooks
            WHERE enabled = 1
                AND JSON_CONTAINS(events, JSON_QUOTE(:event))
                AND (
                    scope = 'instance'
                    OR (scope = 'team' AND teams_id = :team)
                    OR (scope = 'user' AND users_id = :owner)
                )";
        $req = $this->Db->prepare($sql);
        $req->bindValue(':event', $event->value);
        $req->bindValue(':team', $teamId, PDO::PARAM_INT);
        $req->bindValue(':owner', $ownerId, PDO::PARAM_INT);
        $this->Db->execute($req);
        $webhooks = $req->fetchAll();
        if (empty($webhooks)) {
            return 0;
        }

        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $sql = 'INSERT INTO webhooks_queue (webhooks_id, event_id, event, body) VALUES (:webhooks_id, :event_id, :event, :body)';
        $req = $this->Db->prepare($sql);
        $count = 0;
        foreach ($webhooks as $webhook) {
            $req->bindValue(':webhooks_id', $webhook['id'], PDO::PARAM_INT);
            // the same event id on every copy: a subscriber can tell two deliveries of one
            // event apart from two separate events
            $req->bindValue(':event_id', (string) $payload['event_id']);
            $req->bindValue(':event', $event->value);
            $req->bindValue(':body', $body);
            $this->Db->execute($req);
            $count++;
        }
        return $count;
    }

    /**
     * Take ownership of up to $limit due rows and return them with their target.
     *
     * The claim is a two step update/select on a random token instead of a plain select:
     * two overlapping drains would otherwise both pick up the same row and deliver it twice.
     *
     * @return array<int, array<string, mixed>>
     */
    public function claim(int $limit): array
    {
        $token = bin2hex(random_bytes(16));
        $sql = 'UPDATE webhooks_queue
            SET state = :sending, claim_token = :token, attempts = attempts + 1, next_attempt_at = NOW()
            WHERE state = :queued AND next_attempt_at <= NOW()
            ORDER BY id ASC
            LIMIT :limit';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':sending', WebhookState::Sending->value, PDO::PARAM_INT);
        $req->bindValue(':queued', WebhookState::Queued->value, PDO::PARAM_INT);
        $req->bindValue(':token', $token);
        $req->bindValue(':limit', $limit, PDO::PARAM_INT);
        $this->Db->execute($req);

        $sql = 'SELECT q.id, q.event, q.event_id, q.body, q.attempts, w.id AS webhook_id, w.url, w.secret
            FROM webhooks_queue AS q
            INNER JOIN webhooks AS w ON (w.id = q.webhooks_id)
            WHERE q.claim_token = :token
            ORDER BY q.id ASC';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':token', $token);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    public function markDelivered(int $id): void
    {
        $sql = 'UPDATE webhooks_queue
            SET state = :state, delivered_at = NOW(), claim_token = NULL, last_error = NULL
            WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':state', WebhookState::Delivered->value, PDO::PARAM_INT);
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * Put a row back in the queue, due again after $delay seconds.
     */
    public function markRetry(int $id, string $error, int $delay): void
    {
        $sql = 'UPDATE webhooks_queue
            SET state = :state, claim_token = NULL, last_error = :error,
                next_attempt_at = DATE_ADD(NOW(), INTERVAL :delay SECOND)
            WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':state', WebhookState::Queued->value, PDO::PARAM_INT);
        $req->bindValue(':error', $this->truncate($error));
        $req->bindValue(':delay', $delay, PDO::PARAM_INT);
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    public function markFailed(int $id, string $error): void
    {
        $sql = 'UPDATE webhooks_queue SET state = :state, claim_token = NULL, last_error = :error WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':state', WebhookState::Failed->value, PDO::PARAM_INT);
        $req->bindValue(':error', $this->truncate($error));
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * Rows left in Sending by a drain that died. Without this they would never be retried.
     */
    public function releaseStaleClaims(): int
    {
        $sql = 'UPDATE webhooks_queue
            SET state = :queued, claim_token = NULL
            WHERE state = :sending AND next_attempt_at < DATE_SUB(NOW(), INTERVAL :minutes MINUTE)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':queued', WebhookState::Queued->value, PDO::PARAM_INT);
        $req->bindValue(':sending', WebhookState::Sending->value, PDO::PARAM_INT);
        $req->bindValue(':minutes', self::STALE_CLAIM_MINUTES, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->rowCount();
    }

    public function pruneDelivered(): int
    {
        $sql = 'DELETE FROM webhooks_queue
            WHERE state = :state AND delivered_at < DATE_SUB(NOW(), INTERVAL :days DAY)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':state', WebhookState::Delivered->value, PDO::PARAM_INT);
        $req->bindValue(':days', self::KEEP_DELIVERED_DAYS, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->rowCount();
    }

    // the column is a TEXT but there is no value in storing a whole html error page
    private function truncate(string $error): string
    {
        return mb_substr($error, 0, 500);
    }
}
