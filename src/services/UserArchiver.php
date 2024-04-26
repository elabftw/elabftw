<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\AuditEvent\UserAttributeChanged;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\AuditLogs;
use Elabftw\Models\Config;
use Elabftw\Models\Users;
use PDO;

/**
 * Archive/Unarchive a user
 */
class UserArchiver
{
    protected Db $Db;

    public function __construct(private Users $requester, private Users $target)
    {
        $this->Db = Db::getConnection();
    }

    public function toggleArchive(bool $lockExp = false): array
    {
        $this->target->userData['archived'] === 0 ? $this->archive($lockExp) : $this->unarchive();
        if ($this->toggleArchiveSql()) {
            AuditLogs::create(new UserAttributeChanged(
                $this->requester->userid ?? 0,
                $this->target->userid ?? 0,
                'archived',
                (string) $this->target->userData['archived'],
                $this->target->userData['archived'] === 0 ? '1' : '0',
            ));
        }
        return $this->target->readOne();
    }

    private function archive(bool $lockExp = false): bool
    {
        if ($this->target->userData['validated'] === 0) {
            throw new ImproperActionException(_('You are trying to archive an unvalidated user. Maybe you want to delete the account?'));
        }
        if ($this->target->userData['is_sysadmin'] === 1) {
            throw new ImproperActionException(_('A sysadmin account cannot be archived.'));
        }
        if (Config::getConfig()->configArr['admins_archive_users'] === '0' && $this->requester->userData['is_sysadmin'] !== 1) {
            throw new ImproperActionException(_('This instance configuration only permits Sysadmin users to archive a user.'));
        }
        $this->target->invalidateToken();
        // if we are archiving a user, also lock all experiments (if asked)
        return $lockExp ? $this->lockAndArchiveExperiments() : true;
    }

    private function unarchive(): bool
    {
        if ($this->getUnarchivedCount() > 0) {
            throw new ImproperActionException('Cannot unarchive this user because they have another active account with the same email!');
        }
        return true;
    }

    // if the user is already archived, make sure there is no other account with the same email
    private function getUnarchivedCount(): int
    {
        $sql = 'SELECT COUNT(email) FROM users WHERE email = :email AND archived = 0';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':email', $this->target->userData['email']);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    private function toggleArchiveSql(): bool
    {
        $sql = 'UPDATE users SET archived = IF(archived = 1, 0, 1), token = null WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->target->userData['userid'], PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Lock all the experiments owned by user
     */
    private function lockAndArchiveExperiments(): bool
    {
        $sql = 'UPDATE experiments
            SET locked = :locked, lockedby = :lockedby, locked_at = CURRENT_TIMESTAMP, state = :archived WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':locked', 1);
        $req->bindValue(':archived', State::Archived->value, PDO::PARAM_INT);
        $req->bindParam(':lockedby', $this->requester->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':userid', $this->target->userData['userid'], PDO::PARAM_INT);
        return $this->Db->execute($req);
    }
}
