<?php

/**
 * @author Moritz IHLER
 * @copyright 2026 Moritz IHLER
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use DateTimeImmutable;
use DateTimeInterface;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Env;
use Elabftw\Enums\WebhookEvent;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\WebhooksQueue;
use PDO;
use Throwable;

use function bin2hex;
use function error_log;
use function random_bytes;
use function rtrim;
use function sprintf;

/**
 * Turns a changelog write into queued webhook deliveries.
 *
 * This is called from Changelog::create(), which is the one place that already knows
 * "something changed on this entity", so emitting events does not scatter hooks across
 * the models.
 *
 * The payload carries identifiers only, never the entity body: a body would be a second
 * serialization to maintain next to the API, and it would hand out content that the
 * subscriber may not be allowed to read. Subscribers fetch the url with their own api key
 * and get exactly what they are entitled to.
 */
final class WebhookEmitter
{
    /**
     * Events already emitted in this request, keyed by event and entity.
     * An update that touches five columns writes five changelog rows but is one change as far
     * as a subscriber is concerned.
     *
     * @var array<string, true>
     */
    private static array $emitted = array();

    public static function fromChangelog(AbstractEntity $entity, string $target): void
    {
        try {
            self::emit($entity, $target);
        } catch (Throwable $e) {
            // a broken webhook configuration must never make a save fail
            error_log(sprintf('webhook emission failed: %s', $e->getMessage()));
        }
    }

    /**
     * Only used by tests, to get a clean slate between cases.
     */
    public static function reset(): void
    {
        self::$emitted = array();
    }

    private static function emit(AbstractEntity $entity, string $target): void
    {
        if ($entity->id === null) {
            return;
        }
        $event = WebhookEvent::fromChangelogTarget($entity->entityType, $target);
        if ($event === null) {
            return;
        }
        $key = sprintf('%s:%d', $event->value, $entity->id);
        if (isset(self::$emitted[$key])) {
            return;
        }

        $owner = self::readOwner($entity);
        if ($owner === null) {
            return;
        }
        self::$emitted[$key] = true;

        $payload = array(
            'event' => $event->value,
            'event_id' => bin2hex(random_bytes(16)),
            'id' => $entity->id,
            'team' => (int) $owner['team'],
            'url' => sprintf(
                '%s/api/v2/%s/%d',
                rtrim(Env::asUrl('SITE_URL'), '/'),
                $entity->entityType->value,
                $entity->id,
            ),
            'at' => new DateTimeImmutable()->format(DateTimeInterface::ATOM),
            'actor' => array(
                'userid' => $entity->Users->userData['userid'] ?? null,
                // present when the change came through the api, so an integration can
                // recognise and skip the changes it caused itself
                'apikey_id' => $entity->Users->apiKeyId,
            ),
        );

        new WebhooksQueue()->fanout($event, $payload, (int) $owner['team'], (int) $owner['userid']);
    }

    /**
     * Read team and owner straight from the table rather than from entityData: this runs on
     * every write, and it must not depend on how the entity was loaded.
     *
     * @return array{team: int, userid: int}|null
     */
    private static function readOwner(AbstractEntity $entity): ?array
    {
        // the table name comes from an enum, never from user input
        $Db = Db::getConnection();
        $sql = sprintf('SELECT team, userid FROM %s WHERE id = :id', $entity->entityType->value);
        $req = $Db->prepare($sql);
        $req->bindValue(':id', $entity->id, PDO::PARAM_INT);
        $Db->execute($req);
        $res = $req->fetch();
        if ($res === false) {
            return null;
        }
        return array('team' => (int) $res['team'], 'userid' => (int) $res['userid']);
    }
}
