<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\CreateNotificationParams;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Tools;
use Elabftw\Elabftw\UserParams;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Services\EmailValidator;
use Elabftw\Services\Filter;
use Elabftw\Services\TeamsHelper;
use Elabftw\Services\UserArchiver;
use Elabftw\Services\UserCreator;
use Elabftw\Services\UsersHelper;
use Elabftw\Services\UserUpdateRolls;
use PDO;
use Symfony\Component\HttpFoundation\Request;
use function time;

/**
 * Users
 */
class Users implements RestInterface
{
    public bool $needValidation = false;

    public array $userData = array();

    public int $team = 0;

    public self $requester;

    protected Db $Db;

    public function __construct(public ?int $userid = null, ?int $team = null, ?self $requester = null)
    {
        $this->Db = Db::getConnection();
        if ($team !== null) {
            $this->team = $team;
        }
        if ($userid !== null) {
            $this->readOneFull();
        }
        $this->requester = $requester === null ? $this : $requester;
    }

    /**
     * Create a new user
     */
    public function createOne(
        string $email,
        array $teams,
        string $firstname = '',
        string $lastname = '',
        string $passwordHash = '',
        bool $isAdmin = false,
        bool $isSysadmin = false,
        bool $forceValidation = false,
        bool $alertAdmin = true,
        ?string $validUntil = null,
    ): int {
        $Config = Config::getConfig();
        $Teams = new Teams($this);

        // make sure that all the teams in which the user will be are created/exist
        // this might throw an exception if the team doesn't exist and we can't create it on the fly
        $teams = $Teams->getTeamsFromIdOrNameOrOrgidArray($teams);
        $TeamsHelper = new TeamsHelper((int) $teams[0]['id']);

        $EmailValidator = new EmailValidator($email, $Config->configArr['email_domain']);
        $EmailValidator->validate();

        $firstname = Filter::sanitize($firstname);
        $lastname = Filter::sanitize($lastname);

        // Registration date is stored in epoch
        $registerDate = time();

        $isOwner = false;
        $isFirstUser = $TeamsHelper->isFirstUser();
        if ($isFirstUser) {
            $isAdmin = true;
            $isSysadmin = true;
            $isOwner = true;
        } elseif ($TeamsHelper->isFirstUserInTeam()) {
            $isAdmin = true;
            $isOwner = true;
        }

        // will new user be validated?
        $validated = $Config->configArr['admin_validate'] && !$isAdmin ? 0 : 1;
        if ($forceValidation) {
            $validated = 1;
        }

        $defaultRead = BasePermissions::MyTeams->toJson();
        $defaultWrite = BasePermissions::User->toJson();

        $sql = 'INSERT INTO users (
            `email`,
            `password_hash`,
            `firstname`,
            `lastname`,
            `is_sysadmin`,
            `register_date`,
            `validated`,
            `lang`,
            `valid_until`,
            `default_read`,
            `default_write`
        ) VALUES (
            :email,
            :password_hash,
            :firstname,
            :lastname,
            :is_sysadmin,
            :register_date,
            :validated,
            :lang,
            :valid_until,
            :default_read,
            :default_write);';
        $req = $this->Db->prepare($sql);

        $req->bindParam(':email', $email);
        $req->bindParam(':password_hash', $passwordHash);
        $req->bindParam(':firstname', $firstname);
        $req->bindParam(':lastname', $lastname);
        $req->bindParam(':register_date', $registerDate);
        $req->bindParam(':validated', $validated, PDO::PARAM_INT);
        $req->bindParam(':is_sysadmin', $isSysadmin, PDO::PARAM_INT);
        $req->bindValue(':lang', $Config->configArr['lang']);
        $req->bindValue(':valid_until', $validUntil);
        $req->bindValue(':default_read', $defaultRead);
        $req->bindValue(':default_write', $defaultWrite);
        $this->Db->execute($req);
        $userid = $this->Db->lastInsertId();

        // now add the user to the team
        (new Users2Teams())->addUserToTeams($userid, array_column($teams, 'id'), $isAdmin, $isOwner);
        if ($alertAdmin && !$isFirstUser) {
            $this->notifyAdmins($TeamsHelper->getAllAdminsUserid(), $userid, $validated, $teams[0]['name']);
        }
        if ($validated === 0) {
            $Notifications = new Notifications(new self($userid));
            $Notifications->create(new CreateNotificationParams(Notifications::SELF_NEED_VALIDATION));
            // set a flag to show correct message to user
            $this->needValidation = true;
        }
        return $userid;
    }

    /**
     * Search users based on query. It searches in email, firstname, lastname
     *
     * @param string $query the searched term
     * @param int $teamId limit search to a given team or search all teams if 0
     */
    public function readFromQuery(string $query, int $teamId = 0): array
    {
        $teamFilterSql = '';
        $rollColumns = '';
        if ($teamId > 0) {
            $teamFilterSql = ' AND u2t.teams_id = :team';
            // only one team is selected at a time so we can use MIN to get the values
            $rollColumns = 'MIN(u2t.is_owner) as is_owner, MIN(u2t.is_admin) AS is_admin,';
        }

        // Side effect: User is shown in team with lowest id
        $sql = "SELECT users.userid,
            users.firstname, users.lastname, users.email, users.mfa_secret,
            users.validated, users.is_sysadmin, users.archived, users.last_login, users.valid_until,
            CONCAT(users.firstname, ' ', users.lastname) AS fullname,
            users.orcid, users.auth_service,
            ". $rollColumns ."
            JSON_ARRAYAGG(JSON_OBJECT('id', u2t.teams_id, 'name',
                teams.name, 'is_owner', u2t.is_owner, 'is_admin', u2t.is_admin
            )) AS teams
            FROM users
            LEFT JOIN users2teams AS u2t ON (u2t.users_id = users.userid " . $teamFilterSql . ')
            LEFT JOIN teams ON (u2t.teams_id = teams.id)
            WHERE (users.email LIKE :query OR users.firstname LIKE :query OR users.lastname LIKE :query)
                AND teams.id IS NOT NULL
            GROUP BY users.userid
            ORDER BY MIN(u2t.teams_id) ASC, users.is_sysadmin DESC, MAX(u2t.is_owner) DESC,
                MAX(u2t.is_admin) DESC, users.lastname ASC';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':query', '%' . $query . '%');
        if ($teamId > 0) {
            $req->bindValue(':team', $teamId);
        }
        $this->Db->execute($req);

        $users = $req->fetchAll();
        foreach ($users as &$user) {
            $user['teams'] = json_decode($user['teams'], true, 512, JSON_THROW_ON_ERROR);
        }
        return $users;
    }

    /**
     * Read all users from the team
     */
    public function readAllFromTeam(): array
    {
        return $this->readFromQuery('', $this->userData['team']);
    }

    public function readAllActiveFromTeam(): array
    {
        return array_filter($this->readAllFromTeam(), function ($u) {
            return $u['archived'] === 0;
        });
    }

    /**
     * This can be called from api and only contains "safe" values
     */
    public function readAll(): array
    {
        $Request = Request::createFromGlobals();
        return $this->readFromQuerySafe($Request->query->getAlnum('q'), 0);
    }

    /**
     * This can be called from api and only contains "safe" values
     */
    public function readOne(): array
    {
        $this->canReadOrExplode();
        $userData = $this->readOneFull();
        unset($userData['password'], $userData['password_hash'], $userData['salt'], $userData['mfa_secret'], $userData['token']);
        return $userData;
    }

    public function readNamesFromIds(array $idArr): array
    {
        if (empty($idArr)) {
            return array();
        }
        $sql = "SELECT CONCAT(users.firstname, ' ', users.lastname) AS fullname, userid, email FROM users WHERE userid IN (" . implode(',', $idArr) . ') ORDER BY fullname ASC';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function postAction(Action $action, array $reqBody): int
    {
        $Creator = new UserCreator($this->requester, $reqBody);
        return $Creator->create();
    }

    public function patch(Action $action, array $params): array
    {
        $this->canWriteOrExplode();
        match ($action) {
            Action::Add => $this->addUser2Team($params),
            Action::Unreference => (new Users2Teams())->destroy($this->userData['userid'], (int) $params['team']),
            Action::Lock, Action::Archive => (new UserArchiver($this))->toggleArchive(),
            Action::Update => $this->updateWrapper($params),
            Action::Validate => $this->validate(),
            default => throw new ImproperActionException('Invalid action parameter.'),
        };
        return $this->readOne();
    }

    public function getPage(): string
    {
        return 'api/v2/users/';
    }

    /**
     * Invalidate token on logout action
     */
    public function invalidateToken(): bool
    {
        $sql = 'UPDATE users SET token = null WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    public function allowUntrustedLogin(): bool
    {
        $sql = 'SELECT allow_untrusted, auth_lock_time > (NOW() - INTERVAL 1 HOUR) AS currently_locked FROM users WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        $req->execute();
        $res = $req->fetch();

        if ($res['allow_untrusted'] === 1) {
            return true;
        }
        // check for the time when it was locked
        return $res['currently_locked'] === 0;
    }

    /**
     * Destroy user. Will completely remove everything from the user.
     */
    public function destroy(): bool
    {
        $this->canWriteOrExplode();

        $UsersHelper = new UsersHelper($this->userData['userid']);
        if ($UsersHelper->cannotBeDeleted()) {
            throw new ImproperActionException('Cannot delete a user that owns experiments or items!');
        }
        // currently, let's disable this entirely. Next step will be to give this a state and set it to deleted.
        return false;
    }

    /**
     * Check if this instance's user is admin of the userid in function argument
     */
    public function isAdminOf(int $userid): bool
    {
        // consider that we are admin of ourselves
        // consider that a sysadmin is admin of all users
        if ($this->userid === $userid || $this->userData['is_sysadmin']) {
            return true;
        }
        $TeamsHelper = new TeamsHelper($this->userData['team']);
        return $TeamsHelper->isUserInTeam($userid) && $this->userData['is_admin'];
    }

    /**
     * Remove sensitives values from readFromQuery()
     */
    private function readFromQuerySafe(string $query, int $team): array
    {
        $users = $this->readFromQuery($query, $team);
        foreach ($users as &$user) {
            unset($user['mfa_secret']);
        }
        return $users;
    }

    private function canReadOrExplode(): void
    {
        if ($this->requester->userid === $this->userid) {
            // it's ourself
            return;
        }
        if (!$this->requester->userData['is_admin'] && $this->userid !== $this->userData['userid']) {
            throw new IllegalActionException('This endpoint requires admin privileges to access other users.');
        }
        // check we edit user of our team, unless we are sysadmin and we can access it
        if ($this->userid !== null && !$this->requester->isAdminOf($this->userid)) {
            throw new IllegalActionException('User tried to access user from other team.');
        }
    }

    private function updateWrapper(array $params): void
    {
        // first handle user role changes and unset user roll related elements
        (new UserUpdateRolls($this, $this->requester, $params))->update();
        unset($params['is_admin'], $params['admin_of_teams'], $params['is_sysadmin']);

        foreach ($params as $target => $content) {
            $this->update(new UserParams($target, (string) $content));
        }
    }

    private function update(UserParams $params): bool
    {
        // special case for password: we invalidate the stored token
        if ($params->getTarget() === 'password') {
            $this->invalidateToken();
        }
        // email is filtered here because otherwise the check for existing email will throw exception
        if ($params->getTarget() === 'email' && $params->getContent() !== $this->userData['email']) {
            // we can only edit our own email, or be sysadmin
            if (($this->requester->userData['userid'] !== $this->userData['userid']) && (!$this->requester->userData['is_sysadmin'])) {
                throw new IllegalActionException('User tried to edit email of another user but is not sysadmin.');
            }
            Filter::email($params->getContent());
        }

        $sql = 'UPDATE users SET ' . $params->getColumn() . ' = :content WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $params->getContent());
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function addUser2Team(array $params): bool
    {
        $teamProperties = array_filter(
            $this->requester->userData['teams'],
            function (array $requesterTeam) use ($params): bool {
                return $requesterTeam['id'] === (int) $params['team'];
            }
        );

        // requester needs to be sysadmin or admin in target team
        $isAdmin = isset($teamProperties[0]['is_admin']) && $teamProperties[0]['is_admin'];
        if (!$this->requester->userData['is_sysadmin'] && !$isAdmin) {
            throw new IllegalActionException(Tools::error(true));
        }

        return (new Users2Teams())->create(
            $this->userData['userid'],
            (int) $params['team'],
            (bool) $params['is_admin'],
        );
    }

    /**
     * Check if requester can act on this User
     */
    private function canWriteOrExplode(): void
    {
        if (!$this->requester->isAdminOf($this->userData['userid'])) {
            throw new IllegalActionException(Tools::error(true));
        }
    }

    /**
     * Validate current user instance
     * Note: this could also be PATCHed?
     */
    private function validate(): array
    {
        $sql = 'UPDATE users SET validated = 1 WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);
        $Notifications = new Notifications($this);
        $Notifications->create(new CreateNotificationParams(Notifications::SELF_IS_VALIDATED));
        return $this->readOne();
    }

    /**
     * Read all the columns (including sensitive ones) of the current user
     */
    private function readOneFull(): array
    {
        $sql = "SELECT users.*,
            CONCAT(users.firstname, ' ', users.lastname) AS fullname,
            IFNULL(users2teams.is_admin, 0) AS is_admin, IFNULL(users2teams.is_owner, 0) AS is_owner
            FROM users
            LEFT JOIN users2teams ON (users2teams.users_id = users.userid AND users2teams.teams_id = :team)
            WHERE users.userid = :userid";
        $req = $this->Db->prepare($sql);
        $req->bindValue(':userid', $this->userid, PDO::PARAM_INT);
        $req->bindValue(':team', $this->team, PDO::PARAM_INT);
        $this->Db->execute($req);

        $this->userData = $this->Db->fetch($req);
        $this->userData['team'] = $this->team;
        $UsersHelper = new UsersHelper($this->userData['userid']);
        $this->userData['teams'] = $UsersHelper->getTeamsFromUserid();
        return $this->userData;
    }

    private function notifyAdmins(array $admins, int $userid, int $validated, string $team): void
    {
        $body = array(
            'userid' => $userid,
            'team' => $team,
        );
        $notifCat = Notifications::USER_CREATED;
        if ($validated === 0) {
            $notifCat = Notifications::USER_NEED_VALIDATION;
        }
        foreach ($admins as $admin) {
            $Notifications = new Notifications(new self((int) $admin));
            $Notifications->create(new CreateNotificationParams($notifCat, $body));
        }
    }
}
