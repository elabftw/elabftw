<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use Elabftw\Elabftw\Db;
use PDO;
use Throwable;

final class MfaRateLimiter
{
    private const int MAX_FAILURES = 5;

    private Db $Db;

    public function __construct(
        private readonly int $maxFailures = self::MAX_FAILURES,
    ) {
        $this->Db = Db::getConnection();
    }

    public function isBlocked(int $userid): bool
    {
        $sql = 'SELECT
            COALESCE(locked_until > NOW(), 0)
            FROM mfa_rate_limits
            WHERE users_id = :userid';

        $req = $this->Db->prepare($sql);
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $this->Db->execute($req);

        return (int) $req->fetchColumn() === 1;
    }

    /**
     * Register a failure and return true if the user is now blocked.
     */
    public function registerFailure(int $userid): bool
    {
        $this->Db->beginTransaction();

        try {
            // Keep exactly one rate-limit row per user.
            $sql = 'INSERT IGNORE INTO mfa_rate_limits
                (users_id, failed_attempts, first_failed_at)
                VALUES (:userid, 0, NOW())';

            $req = $this->Db->prepare($sql);
            $req->bindValue(':userid', $userid, PDO::PARAM_INT);
            $this->Db->execute($req);

            // Serialize concurrent attempts for the same user.
            $sql = 'SELECT
                failed_attempts,
                first_failed_at > NOW() - INTERVAL 15 MINUTE
                    AS within_window,
                COALESCE(locked_until > NOW(), 0)
                    AS is_blocked
                FROM mfa_rate_limits
                WHERE users_id = :userid
                FOR UPDATE';

            $req = $this->Db->prepare($sql);
            $req->bindValue(':userid', $userid, PDO::PARAM_INT);
            $this->Db->execute($req);

            $row = $this->Db->fetch($req);

            if ((int) $row['is_blocked'] === 1) {
                $this->Db->commit();

                return true;
            }

            $withinWindow = (int) $row['within_window'] === 1;
            $failedAttempts = $withinWindow
                ? (int) $row['failed_attempts'] + 1
                : 1;
            $isBlocked = $failedAttempts >= $this->maxFailures;

            $sql = 'UPDATE mfa_rate_limits
                SET failed_attempts = :failed_attempts,
                    first_failed_at = CASE
                        WHEN :reset_window = 1
                        THEN NOW()
                        ELSE first_failed_at
                    END,
                    locked_until = CASE
                        WHEN :is_blocked = 1
                        THEN NOW() + INTERVAL 15 MINUTE
                        ELSE NULL
                    END
                WHERE users_id = :userid';

            $req = $this->Db->prepare($sql);
            $req->bindValue(':failed_attempts', $failedAttempts, PDO::PARAM_INT);
            $req->bindValue(':reset_window', $withinWindow ? 0 : 1, PDO::PARAM_INT);
            $req->bindValue(':is_blocked', $isBlocked ? 1 : 0, PDO::PARAM_INT);
            $req->bindValue(':userid', $userid, PDO::PARAM_INT);
            $this->Db->execute($req);

            $this->Db->commit();

            return $isBlocked;
        } catch (Throwable $e) {
            $this->Db->rollBack();

            throw $e;
        }
    }

    public function clear(int $userid): void
    {
        $sql = 'DELETE FROM mfa_rate_limits WHERE users_id = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $this->Db->execute($req);
    }
}
