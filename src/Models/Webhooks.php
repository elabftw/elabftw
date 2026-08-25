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
use Elabftw\Enums\WebhookScope;
use Elabftw\Models\Notifications\WebhookDisabled;
use Elabftw\Models\Users\Users;
use Elabftw\Services\TeamsHelper;
use PDO;

use function mb_substr;

/**
 * Operations on the webhooks table that belong to no particular level: this is what the
 * delivery command uses to keep track of how a target is doing.
 */
final class Webhooks
{
    /** after this many consecutive failed deliveries a webhook is turned off */
    public const int FAILURE_CAP = 10;

    private Db $Db;

    public function __construct()
    {
        $this->Db = Db::getConnection();
    }

    /**
     * A delivery went through, so whatever failures came before are no longer consecutive.
     */
    public function recordSuccess(int $id): void
    {
        $sql = 'UPDATE webhooks SET consecutive_failures = 0, last_error = NULL WHERE id = :id AND consecutive_failures > 0';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * A delivery gave up. Past the cap the webhook is disabled, so a target that has been
     * gone for a week stops generating queue rows forever.
     *
     * @return bool true if this failure disabled the webhook
     */
    public function recordFailure(int $id, string $error): bool
    {
        $sql = 'UPDATE webhooks SET consecutive_failures = consecutive_failures + 1, last_error = :error WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':error', mb_substr($error, 0, 500));
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);

        $webhook = $this->readOne($id);
        if ($webhook === null || (int) $webhook['enabled'] !== 1 || (int) $webhook['consecutive_failures'] < self::FAILURE_CAP) {
            return false;
        }
        $this->disable($id);
        $this->notifyOwners($webhook);
        return true;
    }

    public function readOne(int $id): ?array
    {
        $sql = 'SELECT id, scope, teams_id, users_id, url, enabled, consecutive_failures FROM webhooks WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetch();
        return $res === false ? null : $res;
    }

    private function disable(int $id): void
    {
        $sql = 'UPDATE webhooks SET enabled = 0, disabled_at = NOW() WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * Tell whoever can do something about it. Silence would mean an integration quietly
     * stops working and nobody knows why.
     */
    private function notifyOwners(array $webhook): void
    {
        foreach ($this->getOwnersUserid($webhook) as $userid) {
            new WebhookDisabled(new Users($userid), (int) $webhook['id'], (string) $webhook['url'])->create();
        }
    }

    /**
     * @return array<int, int>
     */
    private function getOwnersUserid(array $webhook): array
    {
        return match (WebhookScope::from($webhook['scope'])) {
            WebhookScope::User => array((int) $webhook['users_id']),
            WebhookScope::Team => new TeamsHelper((int) $webhook['teams_id'])->getAllAdminsUserid(),
            WebhookScope::Instance => $this->getSysadmins(),
        };
    }

    /**
     * @return array<int, int>
     */
    private function getSysadmins(): array
    {
        $sql = 'SELECT userid FROM users WHERE is_sysadmin = 1 AND validated = 1';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        return $req->fetchAll(PDO::FETCH_COLUMN);
    }
}
