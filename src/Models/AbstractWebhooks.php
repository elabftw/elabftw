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
use Elabftw\Elabftw\Env;
use Elabftw\Enums\Action;
use Elabftw\Enums\WebhookEvent;
use Elabftw\Enums\WebhookScope;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Services\Filter;
use Elabftw\Services\WebhookUrlValidator;
use Override;
use PDO;
use PDOStatement;

use function array_key_exists;
use function array_keys;
use function bin2hex;
use function in_array;
use function is_array;
use function json_encode;
use function random_bytes;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * Mother class for the three webhook levels: instance, team and user.
 *
 * The three levels share one table, because a webhook is a row with a dozen columns and
 * triplicating that DDL buys nothing. What differs per level is who may write it and which
 * events it is entitled to see, and both of those live outside the table anyway: the first
 * in the controller, the second in WebhooksQueue::fanout().
 */
abstract class AbstractWebhooks extends AbstractRest
{
    public function __construct(
        protected readonly bool $canwrite = false,
        protected readonly ?int $id = null,
    ) {
        $this->Db = Db::getConnection();
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $sql = sprintf(
            'SELECT %s FROM webhooks WHERE scope = :scope AND teams_id <=> :teams_id AND users_id <=> :users_id ORDER BY id ASC',
            $this->getColumns(),
        );
        $req = $this->Db->prepare($sql);
        $this->bindScope($req);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    #[Override]
    public function readOne(): array
    {
        if ($this->id === null) {
            return $this->readAll();
        }
        // the secret is only readable by someone who may write the webhook: it cannot be
        // hashed like an api key (we need it to sign), so the owner has to be able to read it back
        $columns = $this->canwrite ? $this->getColumns() . ', secret' : $this->getColumns();
        $sql = sprintf(
            'SELECT %s FROM webhooks WHERE id = :id AND scope = :scope AND teams_id <=> :teams_id AND users_id <=> :users_id',
            $columns,
        );
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $this->id, PDO::PARAM_INT);
        $this->bindScope($req);
        $this->Db->execute($req);
        return $this->Db->fetch($req);
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        $this->canwriteOrExplode();
        return match ($action) {
            Action::Create => $this->create($reqBody),
            default => throw new ImproperActionException('Incorrect action for webhook.'),
        };
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        $this->canwriteOrExplode();
        return match ($action) {
            Action::Update => $this->update($params),
            default => throw new ImproperActionException('Incorrect action for webhook.'),
        };
    }

    #[Override]
    public function destroy(): bool
    {
        $this->canwriteOrExplode();
        $this->idOrExplode();
        $sql = 'DELETE FROM webhooks WHERE id = :id AND scope = :scope AND teams_id <=> :teams_id AND users_id <=> :users_id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $this->id, PDO::PARAM_INT);
        $this->bindScope($req);
        return $this->Db->execute($req);
    }

    abstract protected function getScope(): WebhookScope;

    abstract protected function getTeamId(): ?int;

    abstract protected function getUserId(): ?int;

    protected function canwriteOrExplode(): void
    {
        if (!$this->canwrite) {
            throw new IllegalActionException();
        }
    }

    protected function create(array $reqBody): int
    {
        $url = $this->getValidator()->validate((string) ($reqBody['url'] ?? ''));
        $events = $this->filterEvents($reqBody['events'] ?? array());
        $sql = 'INSERT INTO webhooks (scope, teams_id, users_id, name, url, secret, events)
            VALUES (:scope, :teams_id, :users_id, :name, :url, :secret, :events)';
        $req = $this->Db->prepare($sql);
        $this->bindScope($req);
        $req->bindValue(':name', Filter::title((string) ($reqBody['name'] ?? '')));
        $req->bindValue(':url', $url);
        // 32 bytes of entropy, stored as 64 hex characters
        $req->bindValue(':secret', bin2hex(random_bytes(32)));
        $req->bindValue(':events', json_encode($events, JSON_THROW_ON_ERROR));
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    protected function update(array $params): array
    {
        $this->idOrExplode();
        // the action is how we got here, it is not a column
        unset($params['action']);
        // an unknown key is a typo on the client side, and silently ignoring it means the
        // caller believes they changed something they did not
        $allowed = array('name', 'url', 'events', 'enabled');
        foreach (array_keys($params) as $key) {
            if (!in_array($key, $allowed, true)) {
                throw new ImproperActionException(sprintf('Invalid parameter for webhook: %s', $key));
            }
        }
        if (array_key_exists('name', $params)) {
            $this->updateColumn('name', Filter::title((string) $params['name']));
        }
        if (array_key_exists('url', $params)) {
            $this->updateColumn('url', $this->getValidator()->validate((string) $params['url']));
        }
        if (array_key_exists('events', $params)) {
            $this->updateColumn('events', json_encode($this->filterEvents($params['events']), JSON_THROW_ON_ERROR));
        }
        if (array_key_exists('enabled', $params)) {
            $enabled = (bool) $params['enabled'];
            $this->updateColumn('enabled', $enabled ? 1 : 0);
            // re-enabling is how an admin acknowledges the failures that disabled it
            if ($enabled) {
                $this->resetFailures();
            }
        }
        return $this->readOne();
    }

    private function getValidator(): WebhookUrlValidator
    {
        return new WebhookUrlValidator(!Env::asBool('DEV_MODE'));
    }

    /**
     * @return array<int, string>
     */
    private function filterEvents(mixed $events): array
    {
        if (!is_array($events)) {
            throw new ImproperActionException(sprintf('Webhook events must be a list. Available values are: %s.', WebhookEvent::toCsList()));
        }
        $filtered = WebhookEvent::filterValid($events);
        if (empty($filtered)) {
            throw new ImproperActionException(sprintf('A webhook must subscribe to at least one valid event. Available values are: %s.', WebhookEvent::toCsList()));
        }
        return $filtered;
    }

    private function updateColumn(string $column, string|int $value): void
    {
        // $column is never user input: it comes from the allow list above
        $sql = sprintf(
            'UPDATE webhooks SET %s = :value WHERE id = :id AND scope = :scope AND teams_id <=> :teams_id AND users_id <=> :users_id',
            $column,
        );
        $req = $this->Db->prepare($sql);
        $req->bindValue(':value', $value);
        $req->bindValue(':id', $this->id, PDO::PARAM_INT);
        $this->bindScope($req);
        $this->Db->execute($req);
    }

    private function resetFailures(): void
    {
        $sql = 'UPDATE webhooks SET consecutive_failures = 0, disabled_at = NULL, last_error = NULL
            WHERE id = :id AND scope = :scope AND teams_id <=> :teams_id AND users_id <=> :users_id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $this->id, PDO::PARAM_INT);
        $this->bindScope($req);
        $this->Db->execute($req);
    }

    private function idOrExplode(): void
    {
        if ($this->id === null) {
            throw new ImproperActionException('Missing webhook id in URL.');
        }
    }

    private function bindScope(PDOStatement $req): void
    {
        $teamId = $this->getTeamId();
        $userId = $this->getUserId();
        $req->bindValue(':scope', $this->getScope()->value);
        $req->bindValue(':teams_id', $teamId, $teamId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $req->bindValue(':users_id', $userId, $userId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    }

    private function getColumns(): string
    {
        return 'id, scope, teams_id, users_id, name, url, events, enabled, consecutive_failures, last_error, disabled_at, created_at, modified_at';
    }
}
