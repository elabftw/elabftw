<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use PDO;

/**
 * Keep track of authentication failures and lock devices or users
 */
final class AuthFail
{
    protected Db $Db;

    public function __construct(private int $loginTries = 0, private int $userid = 0, private ?string $deviceToken = null)
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Register a failed auth attempt, and possibly lock user or device
     */
    public function register(): bool
    {
        $this->create();
        if ($this->deviceToken === null) {
            return $this->countAndLockUser();
        }
        return $this->countAndLockDevice();
    }

    public function getLockedUsersCount(): int
    {
        $sql = 'SELECT COUNT(userid) FROM users WHERE allow_untrusted = 0';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    public function getLockoutDevicesCount(): int
    {
        $sql = 'SELECT COUNT(id) FROM lockout_devices WHERE locked_at > (NOW() - INTERVAL 1 HOUR)';
        $req = $this->Db->prepare($sql);
        $req->execute();
        return (int) $req->fetchColumn();
    }

    /**
     * No device token, count login attempts for that user and lock account for further untrusted devices attempts
     */
    private function countAndLockUser(): bool
    {
        if ($this->countUserAttempts() > $this->loginTries) {
            return $this->lockUserAuth();
        }
        return false;
    }

    /**
     * When device token is present, prevent further login attempts from it if needed
     */
    private function countAndLockDevice(): bool
    {
        if ($this->countDeviceAttempts() > $this->loginTries) {
            return $this->lockDevice();
        }
        return false;
    }

    private function lockDevice(): bool
    {
        $sql = 'INSERT INTO lockout_devices (device_token) VALUES (:device_token)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':device_token', $this->deviceToken);
        return $req->execute();
    }

    /**
     * Register a failed attempt
     */
    private function create(): bool
    {
        $sql = 'INSERT INTO authfail (users_id, device_token) VALUES (:users_id, :device_token)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':users_id', $this->userid, PDO::PARAM_INT);
        $req->bindParam(':device_token', $this->deviceToken);
        return $req->execute();
    }

    /**
     * Count the number of times this device failed to login
     */
    private function countDeviceAttempts(): int
    {
        $sql = 'SELECT COUNT(id) FROM authfail WHERE device_token = :device_token AND attempt_time > (NOW() - INTERVAL 1 HOUR)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':device_token', $this->deviceToken);
        $req->execute();
        return (int) $req->fetchColumn();
    }

    /**
     * Count the number of times this user failed to login
     */
    private function countUserAttempts(): int
    {
        $sql = 'SELECT COUNT(id) FROM authfail WHERE users_id = :users_id AND attempt_time > (NOW() - INTERVAL 1 HOUR)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':users_id', $this->userid, PDO::PARAM_INT);
        $req->execute();
        return (int) $req->fetchColumn();
    }

    /**
     * Prevent all authentication attempts from untrusted devices for a user
     */
    private function lockUserAuth(): bool
    {
        $sql = 'UPDATE users SET allow_untrusted = "0", auth_lock_time = NOW() WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }
}
