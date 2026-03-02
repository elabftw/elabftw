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

use Elabftw\AuditEvent\TeamStatusModified;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\BinaryValue;
use Elabftw\Enums\State;
use Elabftw\Enums\Users2TeamsTargets;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnprocessableContentException;
use Elabftw\Models\AuditLogs;
use Elabftw\Models\Config;
use Elabftw\Models\Users\Users;
use PDO;

/**
 * Archive/Unarchive a user
 */
final class UserArchiver
{
    protected Db $Db;

    public function __construct(private Users $requester, private Users $target)
    {
        $this->Db = Db::getConnection();
    }

    // TODO probably remove the lockExp from here because the ui doesn't provide it
    public function setArchived(BinaryValue $isArchived, bool $lockExp = false): BinaryValue
    {
        $isArchived->toBoolean() ? $this->archive($lockExp) : $this->unarchive();
        if ($this->toggleArchiveSql($isArchived)) {
            /** @psalm-suppress PossiblyNullArgument */
            AuditLogs::create(new TeamStatusModified(
                $this->target->team,
                Users2TeamsTargets::IsArchived,
                $isArchived,
                $this->requester->userid,
                $this->target->userid,
            ));
        }
        return $isArchived;
    }

    private function archive(bool $lockExp = false): bool
    {
        $this->checkArchivePermission();
        if ($this->target->userData['validated'] === 0) {
            throw new ImproperActionException(_('You are trying to archive an unvalidated user. Maybe you want to delete the account?'));
        }
        if ($this->target->userData['is_sysadmin'] === 1) {
            throw new UnprocessableContentException(_('A sysadmin account cannot be archived.'));
        }
        $this->target->invalidateToken();
        // if we are archiving a user, also lock all experiments (if asked)
        return $lockExp ? $this->lockAndArchiveExperiments() : true;
    }

    private function unarchive(): bool
    {
        $this->checkArchivePermission();
        return true;
    }

    private function toggleArchiveSql(BinaryValue $isArchived): bool
    {
        $sql = 'UPDATE users2teams SET is_archived = :is_archived WHERE `users_id` = :userid AND `teams_id` = :team';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':is_archived', $isArchived->value, PDO::PARAM_INT);
        $req->bindValue(':team', $this->target->team, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->target->userData['userid'], PDO::PARAM_INT);
        // also log them out of current session
        $this->target->invalidateToken();
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

    // check if the admin can archive/unarchive a user
    private function checkArchivePermission(): void
    {
        if (Config::getConfig()->configArr['admins_archive_users'] === '0' &&
            $this->requester->userData['is_sysadmin'] !== 1) {
            throw new IllegalActionException();
        }
        // make sure requester is admin of target user
        if (!$this->requester->isAdminOf($this->target->userid ?? 0) && $this->requester->userData['can_manage_users2teams'] === 0) {
            throw new IllegalActionException('User tried to patch is_archived of another user but they are not admin');
        }
    }
}
