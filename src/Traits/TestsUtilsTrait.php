<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Traits;

use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\NullLocalPassword;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\Usergroup;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Users\AuthenticatedUser;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\ResourcesCategories;
use Elabftw\Models\Teams;
use Elabftw\Models\Templates;
use Elabftw\Models\Users\Users;
use PDO;

use function array_rand;
use function array_map;
use function bin2hex;
use function implode;
use function intval;
use function random_bytes;
use function sprintf;

trait TestsUtilsTrait
{
    public static function entityTypes(): array
    {
        return array_map(static fn(EntityType $type): array => array($type), EntityType::cases());
    }

    public static function concreteEntityTypes(): array
    {
        return array(
            'experiments' => array(EntityType::Experiments),
            'resources' => array(EntityType::Items),
        );
    }

    protected function getUserInTeam(int $team, int $admin = 0, int $archived = 0): AuthenticatedUser
    {
        $Db = Db::getConnection();
        $sql = 'SELECT * FROM users2teams INNER JOIN users ON users.userid = users2teams.users_id WHERE users.validated = 1 AND teams_id = :team AND is_admin = :admin AND is_archived = :archived LIMIT 1';
        $req = $Db->prepare($sql);
        $req->bindValue(':team', $team, PDO::PARAM_INT);
        $req->bindValue(':admin', $admin, PDO::PARAM_INT);
        $req->bindValue(':archived', $archived, PDO::PARAM_INT);
        $Db->execute($req);
        $res = $req->fetch();
        return new AuthenticatedUser($res['users_id'], $team);
    }

    protected function getRandomUserInTeam(int $team, int $admin = 0, int $archived = 0): AuthenticatedUser
    {
        $Db = Db::getConnection();
        $sql = 'SELECT * FROM users2teams INNER JOIN users ON users.userid = users2teams.users_id WHERE users.validated = 1 AND (users.valid_until IS NULL OR users.valid_until > NOW()) AND teams_id = :team AND is_admin = :admin AND is_archived = :archived';
        $req = $Db->prepare($sql);
        $req->bindValue(':team', $team, PDO::PARAM_INT);
        $req->bindValue(':admin', $admin, PDO::PARAM_INT);
        $req->bindValue(':archived', $archived, PDO::PARAM_INT);
        $Db->execute($req);
        $res = $req->fetchAll();
        $selected = $res[array_rand($res)];
        return new AuthenticatedUser($selected['users_id'], $team);
    }

    /**
     * An active and validated user of the team, with no admin or sysadmin rights
     * Pass the users already used by the test to get a different one.
     *
     * @param list<int> $excludedUserids
     */
    protected function getRegularUserInTeam(int $team, array $excludedUserids = array()): AuthenticatedUser
    {
        $exclude = $excludedUserids === array() ? '' : sprintf(
            'AND u2t.users_id NOT IN (%s)',
            implode(',', array_map(intval(...), $excludedUserids)),
        );
        $sql = sprintf('SELECT u2t.users_id FROM users2teams AS u2t
            INNER JOIN users AS u ON u.userid = u2t.users_id
            WHERE u2t.teams_id = :team
                AND u2t.is_admin = 0
                AND u2t.is_archived = 0
                AND u.is_sysadmin = 0
                AND u.validated = 1
                AND (u.valid_until IS NULL OR u.valid_until > NOW())
                %s
            ORDER BY u2t.users_id LIMIT 1', $exclude);
        $Db = Db::getConnection();
        $req = $Db->prepare($sql);
        $req->bindValue(':team', $team, PDO::PARAM_INT);
        $Db->execute($req);
        $userid = $req->fetchColumn();
        if ($userid === false) {
            throw new ResourceNotFoundException(sprintf('Not enough regular users in team %d.', $team));
        }
        return new AuthenticatedUser((int) $userid, $team);
    }

    /**
     * A regular user promoted to admin of the team, so they are admin there and nowhere else by construction
     * Writes to the database: use it inside a transaction that is rolled back.
     *
     * @param list<int> $excludedUserids
     */
    protected function getFreshAdminInTeam(int $team, array $excludedUserids = array()): AuthenticatedUser
    {
        $userid = $this->getRegularUserInTeam($team, $excludedUserids)->getUserid();
        $this->setTeamMembership($userid, $team, isAdmin: 1);
        return new AuthenticatedUser($userid, $team);
    }

    /**
     * A regular user promoted to sysadmin, without admin rights in the team
     * Writes to the database: use it inside a transaction that is rolled back.
     *
     * @param list<int> $excludedUserids
     */
    protected function getFreshSysadminInTeam(int $team, array $excludedUserids = array()): AuthenticatedUser
    {
        $userid = $this->getRegularUserInTeam($team, $excludedUserids)->getUserid();
        $Db = Db::getConnection();
        $req = $Db->prepare('UPDATE users SET is_sysadmin = 1 WHERE userid = :userid');
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $Db->execute($req);
        return new AuthenticatedUser($userid, $team);
    }

    /**
     * Add the user to the team or overwrite their flags there, so leftovers (e.g. an archived membership) cannot leak into a test
     * Writes to the database: use it inside a transaction that is rolled back.
     */
    protected function setTeamMembership(int $userid, int $team, int $isAdmin = 0, int $isArchived = 0): void
    {
        $Db = Db::getConnection();
        $req = $Db->prepare('INSERT IGNORE INTO users2teams (users_id, teams_id) VALUES (:userid, :team)');
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $req->bindValue(':team', $team, PDO::PARAM_INT);
        $Db->execute($req);
        $req = $Db->prepare('UPDATE users2teams SET is_admin = :is_admin, is_archived = :is_archived WHERE users_id = :userid AND teams_id = :team');
        $req->bindValue(':is_admin', $isAdmin, PDO::PARAM_INT);
        $req->bindValue(':is_archived', $isArchived, PDO::PARAM_INT);
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $req->bindValue(':team', $team, PDO::PARAM_INT);
        $Db->execute($req);
    }

    protected function getUserIdFromEmail(string $email): int
    {
        $Db = Db::getConnection();
        $sql = 'SELECT userid FROM users WHERE email = :email AND validated = 1 LIMIT 1';
        $req = $Db->prepare($sql);
        $req->bindValue(':email', $email);
        $Db->execute($req);
        return (int) $req->fetchColumn();
    }

    protected function getFreshExperiment(): Experiments
    {
        $Entity = new Experiments(new Users(1, 1));
        $id = $Entity->create();
        $Entity->setId($id);
        return $Entity;
    }

    protected function getFreshExperimentWithGivenUser(Users $users): Experiments
    {
        $Entity = new Experiments($users);
        $id = $Entity->create();
        $Entity->setId($id);
        return $Entity;
    }

    /**
     * A fresh entity of any type owned by the given user
     * With $base, both read and write base permissions are set to it, otherwise the model defaults apply.
     */
    protected function getFreshEntityWithGivenUser(EntityType $type, Users $users, ?BasePermissions $base = null): AbstractEntity
    {
        $Entity = $type->toInstance($users);
        $id = $base === null ? $Entity->create() : $Entity->create(canreadBase: $base, canwriteBase: $base);
        $Entity->setId($id);
        return $Entity;
    }

    protected function getFreshItem(int $team = 1): Items
    {
        $User = $this->getRandomUserInTeam($team);
        $Entity = new Items($User);
        $id = $Entity->create();
        $Entity->setId($id);
        return $Entity;
    }

    protected function getFreshItemWithGivenUser(Users $users): Items
    {
        $Entity = new Items($users);
        $id = $Entity->create();
        $Entity->setId($id);
        return $Entity;
    }

    protected function getFreshBookableItem(int $team): Items
    {
        $Item = $this->getFreshItem($team);
        $ResourcesCategories = new ResourcesCategories(new Teams($Item->Users, $team));
        $category = (string) $ResourcesCategories->readAll()[0]['id'];
        $Item->patch(Action::Update, array(
            'is_bookable' => '1',
            'booking_hourly_rate_notax' => 10.00,
            'category' => $category,
        ));
        return $Item;
    }

    protected function getFreshTemplate(): Templates
    {
        $Entity = new Templates(new Users(1, 1));
        $id = $Entity->create();
        $Entity->setId($id);
        return $Entity;
    }

    protected function getFreshItemType(): ItemsTypes
    {
        $Entity = new ItemsTypes(new Users(1, 1));
        $id = $Entity->create();
        $Entity->setId($id);
        return $Entity;
    }

    /**
     * Create a team whose only member, its former Admin, is archived
     */
    protected function getFreshFullyArchivedTeam(): int
    {
        $Sysadmin = new Users(1, 1);
        $teamId = new Teams($Sysadmin)->postAction(Action::Create, array('name' => 'Fully archived team'));
        $userid = new Users(null, null, $Sysadmin)->createOne(
            'archived-member-' . bin2hex(random_bytes(8)) . '@example.com',
            array($teamId),
            new NullLocalPassword(),
            'Archived',
            'Member',
            usergroup: Usergroup::Admin,
            automaticValidationEnabled: true,
            alertAdmin: false,
        );
        $Db = Db::getConnection();
        $req = $Db->prepare('UPDATE users2teams SET is_archived = 1 WHERE users_id = :userid AND teams_id = :team');
        $req->bindValue(':userid', $userid, PDO::PARAM_INT);
        $req->bindValue(':team', $teamId, PDO::PARAM_INT);
        $Db->execute($req);
        return $teamId;
    }

    protected function updateArchiveStatus(int $userid, int $is_archived): void
    {
        $Db = Db::getConnection();
        $sql = 'UPDATE users2teams SET is_archived = :is_archived WHERE users_id = :userid';
        $req = $Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $req->bindValue(':is_archived', $is_archived, PDO::PARAM_INT);
        $Db->execute($req);
    }
}
