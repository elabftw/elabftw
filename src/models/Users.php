<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\AuditEvent\EmailChanged;
use Elabftw\AuditEvent\IsSysadminChanged;
use Elabftw\AuditEvent\OrgidChanged;
use Elabftw\AuditEvent\PasswordChanged;
use Elabftw\AuditEvent\UserRegister;
use Elabftw\Auth\Local;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Tools;
use Elabftw\Elabftw\UserParams;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Models\Notifications\SelfIsValidated;
use Elabftw\Models\Notifications\SelfNeedValidation;
use Elabftw\Models\Notifications\UserCreated;
use Elabftw\Models\Notifications\UserNeedValidation;
use Elabftw\Services\EmailValidator;
use Elabftw\Services\Filter;
use Elabftw\Services\MfaHelper;
use Elabftw\Services\TeamsHelper;
use Elabftw\Services\UserArchiver;
use Elabftw\Services\UserCreator;
use Elabftw\Services\UsersHelper;
use PDO;
use Symfony\Component\HttpFoundation\Request;
use function time;
use function trim;

/**
 * Users
 */
class Users implements RestInterface
{
    public bool $needValidation = false;

    public array $userData = array();

    public int $team = 0;

    public self $requester;

    public bool $isAdmin = false;

    protected Db $Db;

    public function __construct(public ?int $userid = null, ?int $team = null, ?self $requester = null)
    {
        $this->Db = Db::getConnection();
        if ($team !== null && $userid !== null) {
            $this->team = $team;
            $TeamsHelper = new TeamsHelper($team);
            $permissions = $TeamsHelper->getPermissions($userid);
            $this->isAdmin = (bool) $permissions['is_admin'];
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

        $firstname = trim(Filter::sanitize($firstname));
        $lastname = trim(Filter::sanitize($lastname));

        // Registration date is stored in epoch
        $registerDate = time();

        // get the group for the new user
        if ($group === null) {
            $group = $TeamsHelper->getGroup();
        }

        $isSysadmin = $group === 1 ? 1 : 0;

        // transform group in 2 if it is 1 because users2teams.groups_id is 2 or 4, not 1
        if ($group === 1) {
            $group = 2;
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
            `register_date`,
            `validated`,
            `lang`,
            `valid_until`,
            `orgid`,
            `is_sysadmin`,
            `default_read`,
            `default_write`
        ) VALUES (
            :email,
            :password_hash,
            :firstname,
            :lastname,
            :register_date,
            :validated,
            :lang,
            :valid_until,
            :orgid,
            :is_sysadmin,
            :default_read,
            :default_write);';
        $req = $this->Db->prepare($sql);

        $req->bindParam(':email', $email);
        $req->bindParam(':password_hash', $passwordHash);
        $req->bindParam(':firstname', $firstname);
        $req->bindParam(':lastname', $lastname);
        $req->bindParam(':register_date', $registerDate);
        $req->bindParam(':validated', $validated, PDO::PARAM_INT);
        $req->bindValue(':lang', $Config->configArr['lang']);
        $req->bindValue(':valid_until', $validUntil);
        $req->bindValue(':orgid', $orgid);
        $req->bindValue(':is_sysadmin', $isSysadmin);
        $req->bindValue(':default_read', $defaultRead);
        $req->bindValue(':default_write', $defaultWrite);
        $this->Db->execute($req);
        $userid = $this->Db->lastInsertId();

        // check if the team is empty before adding the user to the team
        $isFirstUser = $TeamsHelper->isFirstUserInTeam();
        // now add the user to the team
        $Users2Teams = new Users2Teams();
        $Users2Teams->addUserToTeams($userid, array_column($teams, 'id'), $group);
        if ($alertAdmin && !$isFirstUser) {
            $this->notifyAdmins($TeamsHelper->getAllAdminsUserid(), $userid, $validated, $teams[0]['name']);
        }
        if ($validated === 0) {
            $Notifications = new SelfNeedValidation();
            $Notifications->create($userid);
            // set a flag to show correct message to user
            $this->needValidation = true;
        }
        AuditLogs::create(new UserRegister($userid));
        return $userid;
    }

    /**
     * Search users based on query. It searches in email, firstname, lastname
     *
     * @param string $query the searched term
     * @param int $teamId limit search to a given team or search all teams if 0
     */
    public function readFromQuery(
        string $query,
        int $teamId = 0,
        bool $includeArchived = false,
        bool $onlyAdmins = false,
    ): array {
        $teamFilterSql = '';
        if ($teamId > 0) {
            $teamFilterSql = ' AND users2teams.teams_id = :team';
        }

        // Assures to get every user only once
        $tmpTable = ' (SELECT users_id, MIN(teams_id) AS teams_id, MIN(groups_id) AS groups_id
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

        $admins = '';
        if ($onlyAdmins) {
            $admins = 'AND users2teams.groups_id = 2';
        }

        // NOTE: $tmpTable avoids the use of DISTINCT, so we are able to use ORDER BY with teams_id.
        // Side effect: User is shown in team with lowest id
        $sql = "SELECT users.userid,
            users.firstname, users.lastname, users.orgid, users.email, users.mfa_secret,
            users.validated, users.archived, users.last_login, users.valid_until, users.is_sysadmin,
            CONCAT(users.firstname, ' ', users.lastname) AS fullname,
            users.orcid, users.auth_service
            FROM users
            CROSS JOIN" . $tmpTable . ' users2teams ON (users2teams.users_id = users.userid' . $teamFilterSql . ' ' . $admins . ')
            WHERE (users.email LIKE :query OR users.firstname LIKE :query OR users.lastname LIKE :query)
            AND (users.archived = 0 ' . $archived . ')
            ORDER BY users2teams.teams_id ASC, users.lastname ASC';
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
        return $this->readFromQuery('', $this->userData['team'], true);
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

    public function isAdminSomewhere(): bool
    {
        $sql = 'SELECT users_id FROM users2teams WHERE users_id = :userid AND groups_id <= 2';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userData['userid']);
        $this->Db->execute($req);
        return $req->rowCount() >= 1;
    }

    public function postAction(Action $action, array $reqBody): int
    {
        $Creator = new UserCreator($this->requester, $reqBody);
        return $Creator->create();
    }

    public function patch(Action $action, array $params): array
    {
        $this->canWriteOrExplode($action);
        match ($action) {
            Action::Add => (
                function () use ($params) {
                    // check instance config if admins are allowed to do that (if requester is not sysadmin)
                    $Config = Config::getConfig();
                    if ($this->requester->userData['is_sysadmin'] !== 1 && $Config->configArr['admins_import_users'] !== '1') {
                        throw new IllegalActionException('A non sysadmin user tried to import a user but admins_import_users is disabled in config.');
                    }
                    // need to be admin to "import" a user in a team
                    $team = (int) $params['team'];
                    $TeamsHelper = new TeamsHelper($team);
                    $permissions = $TeamsHelper->getPermissions($this->requester->userData['userid']);
                    if ($permissions['is_admin'] !== 1 && $this->requester->userData['is_sysadmin'] !== 1) {
                        throw new IllegalActionException('Only Admin can add a user to a team (where they are Admin)');
                    }
                    (new Users2Teams())->create($this->userData['userid'], $team);
                }
            )(),
            Action::Disable2fa => $this->disable2fa(),
            Action::PatchUser2Team => (new Users2Teams())->PatchUser2Team($this->requester, $params),
            Action::Unreference => (new Users2Teams())->destroy($this->userData['userid'], (int) $params['team']),
            Action::Lock, Action::Archive => (new UserArchiver($this))->toggleArchive((bool) $params['with_exp']),
            Action::UpdatePassword => $this->updatePassword($params),
            Action::Update => (
                function () use ($params) {
                    foreach ($params as $target => $content) {
                        $this->update(new UserParams($target, (string) $content));
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
            throw new ImproperActionException('Cannot delete a user that owns experiments, items, comments, templates or uploads!');
        }
        $sql = 'DELETE FROM users WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        $sql = 'DELETE FROM users2teams WHERE users_id = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        $sql = 'DELETE FROM users2team_groups WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        $sql = 'DELETE FROM todolist WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        $sql = 'DELETE FROM team_events WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        $sql = 'DELETE FROM notifications WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        $sql = 'DELETE FROM favtags2users WHERE users_id = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        return true;
    }

    /**
     * Check if this instance's user is admin of the userid in function argument
     */
    public function isAdminOf(int $userid): bool
    {
        // consider that we are admin of ourselves
        if ($this->userid === $userid) {
            return true;
        }
        // check if in the teams we have in common, the potential admin is admin
        $sql = 'SELECT * FROM users2teams u1
                INNER JOIN users2teams u2 ON u1.teams_id = u2.teams_id
                WHERE u1.users_id = :admin_userid AND u2.users_id = :user_userid AND u1.groups_id <= 2';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':admin_userid', $this->userid, PDO::PARAM_INT);
        $req->bindParam(':user_userid', $userid, PDO::PARAM_INT);
        $req->execute();
        return $req->rowCount() >= 1;
    }

    /**
     * This function allows us to set a new password without having to provide the old password
     */
    public function resetPassword(string $password): bool
    {
        return $this->updatePassword(array('password' => $password), true);
    }

    public function checkCurrentPasswordOrExplode(?string $currentPassword): void
    {
        if (empty($currentPassword)) {
            throw new ImproperActionException('Current password must be provided by "current_password" parameter.');
        }
        $LocalAuth = new Local($this->userData['email'], $currentPassword);
        try {
            $LocalAuth->tryAuth();
        } catch (InvalidCredentialsException) {
            throw new ImproperActionException('The current password is not valid!');
        }
    }

    protected static function search(string $column, string $term, bool $validated = false): self
    {
        $searchColumn = 'email';
        if ($column === 'orgid') {
            $searchColumn = 'orgid';
        }
        $validatedFilter = '';
        if ($validated) {
            $validatedFilter = ' AND validated = 1 ';
        }
        $Db = Db::getConnection();
        $sql = sprintf('SELECT userid FROM users WHERE %s = :term AND archived = 0 %s LIMIT 1', $searchColumn, $validatedFilter);
        $req = $Db->prepare($sql);
        $req->bindParam(':term', $term);
        $Db->execute($req);
        $res = $req->fetchColumn();
        if ($res === false) {
            throw new ResourceNotFoundException();
        }
        return new self((int) $res);
    }

    private function disable2fa(): array
    {
        // only sysadmin or same user can disable 2fa
        if ($this->requester->userData['userid'] === $this->userData['userid'] || $this->requester->userData['is_sysadmin'] === 1) {
            (new MfaHelper($this->userData['userid']))->removeSecret();
            return $this->readOne();
        }
        throw new IllegalActionException('User tried to disable 2fa but is not sysadmin or same user.');
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
        // it's ourself or we are sysadmin
        if ($this->requester->userid === $this->userid || $this->requester->userData['is_sysadmin'] === 1) {
            return;
        }
        if (!$this->requester->isAdmin && $this->userid !== $this->userData['userid']) {
            throw new IllegalActionException('This endpoint requires admin privileges to access other users.');
        }
        // check we view user of our team, unless we are sysadmin and we can access it
        if ($this->userid !== null && !$this->requester->isAdminOf($this->userid)) {
            throw new IllegalActionException('User tried to access user from other team.');
        }
    }

    private function update(UserParams $params): bool
    {
        if ($params->getTarget() === 'password') {
            throw new ImproperActionException('Use action:updatepassword to update the password');
        }
        // email is filtered here because otherwise the check for existing email will throw exception
        if ($params->getTarget() === 'email' && $params->getContent() !== $this->userData['email']) {
            // we can only edit our own email, or be sysadmin
            if (($this->requester->userData['userid'] !== $this->userData['userid']) && ($this->requester->userData['is_sysadmin'] !== 1)) {
                throw new IllegalActionException('User tried to edit email of another user but is not sysadmin.');
            }
            AuditLogs::create(new EmailChanged($this->requester->userid));
            Filter::email($params->getContent());
        }
        // special case for is_sysadmin: only a sysadmin can affect this column
        if ($params->getTarget() === 'is_sysadmin') {
            if ($this->requester->userData['is_sysadmin'] === 0) {
                throw new IllegalActionException('Non sysadmin user tried to edit the is_sysadmin column of a user');
            }
            /** @psalm-suppress PossiblyNullArgument */
            AuditLogs::create(new IsSysadminChanged($this->requester->userid, (int) $params->getContent(), $this->userData['userid']));
        }

        // log uid change
        if ($params->getTarget() === 'orgid') {
            AuditLogs::create(new OrgidChanged($this->requester->userid));
        }

        $sql = 'UPDATE users SET ' . $params->getColumn() . ' = :content WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $params->getContent());
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        $res = $this->Db->execute($req);
        if ($res && in_array($params->getTarget(), $auditLoggableTargets, true)) {
            AuditLogs::create(new UserAttributeChanged($this->requester->userid));
        }
        return $res;
    }

    private function updatePassword(array $params, bool $isReset = false): bool
    {
        // a sysadmin or reset password page request doesn't need to provide the current password
        if ($this->requester->userData['is_sysadmin'] !== 1 && $isReset === false) {
            $this->checkCurrentPasswordOrExplode($params['current_password']);
        }
        if (empty($params['password'])) {
            throw new ImproperActionException('New password must be provided by "password" parameter.');
        }
        // when updating the password, we need to check for the presence and validity of the current_password
        // special case for password: we invalidate the stored token
        $this->invalidateToken();
        // this will properly hash the password
        $params = new UserParams('password', $params['password']);
        // don't use the update() function so it cannot be bypassed by setting Action::Update instead of Action::UpdatePassword
        $sql = 'UPDATE users SET password_hash = :content WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $params->getContent());
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        $res = $this->Db->execute($req);
        /** @psalm-suppress PossiblyNullArgument */
        AuditLogs::create(new PasswordChanged($this->userid));
        return $res;
    }

    /**
     * Check if requester can act on this User
     */
    private function canWriteOrExplode(?Action $action = null): void
    {
        if ($this->requester->userData['is_sysadmin'] === 1) {
            return;
        }
        if (!$this->requester->isAdminOf($this->userData['userid']) && $action !== Action::Add) {
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
            CONCAT(users.firstname, ' ', users.lastname) AS fullname
            FROM users WHERE users.userid = :userid";
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
