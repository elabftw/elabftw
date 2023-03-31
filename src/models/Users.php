<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Tools;
use Elabftw\Elabftw\UserParams;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Models\Notifications\SelfIsValidated;
use Elabftw\Models\Notifications\SelfNeedValidation;
use Elabftw\Models\Notifications\UserCreated;
use Elabftw\Models\Notifications\UserNeedValidation;
use Elabftw\Services\EmailValidator;
use Elabftw\Services\Filter;
use Elabftw\Services\TeamsHelper;
use Elabftw\Services\UserArchiver;
use Elabftw\Services\UserCreator;
use Elabftw\Services\UsersHelper;
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
        ?int $group = null,
        bool $forceValidation = false,
        bool $alertAdmin = true,
        ?string $validUntil = null,
        ?string $orgid = null,
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

        // get the group for the new user
        if ($group === null) {
            $group = $TeamsHelper->getGroup();
        }

        // will new user be validated?
        $validated = $Config->configArr['admin_validate'] && ($group === 4) ? 0 : 1;
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
            `usergroup`,
            `register_date`,
            `validated`,
            `lang`,
            `valid_until`,
            `orgid`,
            `default_read`,
            `default_write`
        ) VALUES (
            :email,
            :password_hash,
            :firstname,
            :lastname,
            :usergroup,
            :register_date,
            :validated,
            :lang,
            :valid_until,
            :orgid,
            :default_read,
            :default_write);';
        $req = $this->Db->prepare($sql);

        $req->bindParam(':email', $email);
        $req->bindParam(':password_hash', $passwordHash);
        $req->bindParam(':firstname', $firstname);
        $req->bindParam(':lastname', $lastname);
        $req->bindParam(':register_date', $registerDate);
        $req->bindParam(':validated', $validated, PDO::PARAM_INT);
        $req->bindParam(':usergroup', $group, PDO::PARAM_INT);
        $req->bindValue(':lang', $Config->configArr['lang']);
        $req->bindValue(':valid_until', $validUntil);
        $req->bindValue(':orgid', $orgid);
        $req->bindValue(':default_read', $defaultRead);
        $req->bindValue(':default_write', $defaultWrite);
        $this->Db->execute($req);
        $userid = $this->Db->lastInsertId();

        // check if the team is empty before adding the user to the team
        $isFirstUser = $TeamsHelper->isFirstUserInTeam();
        // now add the user to the team
        $Users2Teams = new Users2Teams();
        $Users2Teams->addUserToTeams($userid, array_column($teams, 'id'));
        if ($alertAdmin && !$isFirstUser) {
            $this->notifyAdmins($TeamsHelper->getAllAdminsUserid(), $userid, $validated, $teams[0]['name']);
        }
        if ($validated === 0) {
            $Notifications = new SelfNeedValidation();
            $Notifications->create($userid);
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
    public function readFromQuery(string $query, int $teamId = 0, bool $includeArchived = false): array
    {
        $teamFilterSql = '';
        if ($teamId > 0) {
            $teamFilterSql = ' AND users2teams.teams_id = :team';
        }

        // Assures to get every user only once
        $tmpTable = ' (SELECT users_id, MIN(teams_id) AS teams_id
            FROM users2teams
            GROUP BY users_id) AS';
        // unless we use a specific team
        if ($teamId > 0) {
            $tmpTable = '';
        }

        $archived = '';
        if ($includeArchived) {
            $archived = 'OR users.archived = 1';
        }

        // NOTE: $tmpTable avoids the use of DISTINCT, so we are able to use ORDER BY with teams_id.
        // Side effect: User is shown in team with lowest id
        $sql = "SELECT users.userid,
            users.firstname, users.lastname, users.orgid, users.email, users.mfa_secret,
            users.validated, users.usergroup, users.archived, users.last_login, users.valid_until,
            CONCAT(users.firstname, ' ', users.lastname) AS fullname,
            users.orcid, users.auth_service
            FROM users
            CROSS JOIN" . $tmpTable . ' users2teams ON (users2teams.users_id = users.userid' . $teamFilterSql . ')
            WHERE (users.email LIKE :query OR users.firstname LIKE :query OR users.lastname LIKE :query)
            AND users.archived = 0 ' . $archived . '
            ORDER BY users2teams.teams_id ASC, users.usergroup ASC, users.lastname ASC';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':query', '%' . $query . '%');
        if ($teamId > 0) {
            $req->bindValue(':team', $teamId);
        }
        $this->Db->execute($req);

        return $req->fetchAll();
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
        unset($userData['password']);
        unset($userData['password_hash']);
        unset($userData['salt']);
        unset($userData['mfa_secret']);
        unset($userData['token']);
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
            Action::Add => (new Users2Teams())->create($this->userData['userid'], (int) $params['team']),
            Action::Unreference => (new Users2Teams())->destroy($this->userData['userid'], (int) $params['team']),
            Action::Lock, Action::Archive => (new UserArchiver($this))->toggleArchive(),
            Action::Update => (
                function () use ($params) {
                    $isSysadmin = 0;
                    $isAdmin = 0;
                    $targetIsSysadmin = 0;
                    if ($this->requester instanceof self) {
                        $isSysadmin = $this->requester->userData['is_sysadmin'];
                        $isAdmin = $this->requester->userData['is_admin'];
                        $targetIsSysadmin = $this->userData['is_sysadmin'];
                    }
                    foreach ($params as $target => $content) {
                        $this->update(new UserParams($target, (string) $content, $isSysadmin, $isAdmin, $targetIsSysadmin));
                    }
                }
            )(),
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
        throw new ImproperActionException('Complete user deletion is temporarily deactivated. Use Archive button instead.');
    }

    /**
     * Check if this instance's user is admin of the userid in function argument
     */
    public function isAdminOf(int $userid): bool
    {
        // consider that we are admin of ourselves
        // consider that a sysadmin is admin of all users
        if ($this->userid === $userid || $this->userData['is_sysadmin'] === 1) {
            return true;
        }
        $TeamsHelper = new TeamsHelper($this->userData['team']);
        return $TeamsHelper->isUserInTeam($userid) && $this->userData['is_admin'] === 1;
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
        if ($this->requester->userData['is_admin'] !== 1 && $this->userid !== $this->userData['userid']) {
            throw new IllegalActionException('This endpoint requires admin privileges to access other users.');
        }
        // check we edit user of our team, unless we are sysadmin and we can access it
        if ($this->userid !== null && !$this->requester->isAdminOf($this->userid)) {
            throw new IllegalActionException('User tried to access user from other team.');
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
            if (($this->requester->userData['userid'] !== $this->userData['userid']) && ($this->requester->userData['is_sysadmin'] !== 1)) {
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
        $Notifications = new SelfIsValidated();
        $Notifications->create($this->userData['userid']);
        return $this->readOne();
    }

    /**
     * Read all the columns (including sensitive ones) of the current user
     */
    private function readOneFull(): array
    {
        $sql = "SELECT users.*,
            CONCAT(users.firstname, ' ', users.lastname) AS fullname,
            groups.is_admin, groups.is_sysadmin
            FROM users
            LEFT JOIN `groups` ON groups.id = users.usergroup
            WHERE users.userid = :userid";
        $req = $this->Db->prepare($sql);
        $req->bindValue(':userid', $this->userid, PDO::PARAM_INT);
        $this->Db->execute($req);

        $this->userData = $this->Db->fetch($req);
        $this->userData['team'] = $this->team;
        $UsersHelper = new UsersHelper($this->userData['userid']);
        $this->userData['teams'] = $UsersHelper->getTeamsFromUserid();
        return $this->userData;
    }

    private function notifyAdmins(array $admins, int $userid, int $validated, string $team): void
    {
        $Notifications = new UserCreated($userid, $team);
        if ($validated === 0) {
            $Notifications = new UserNeedValidation($userid, $team);
        }
        foreach ($admins as $admin) {
            $Notifications->create((int) $admin);
        }
    }
}
