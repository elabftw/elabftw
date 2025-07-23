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

use Elabftw\AuditEvent\PasswordChanged;
use Elabftw\AuditEvent\UserAttributeChanged;
use Elabftw\AuditEvent\UserDeleted;
use Elabftw\AuditEvent\UserRegister;
use Elabftw\Auth\Local;
use Elabftw\Elabftw\App;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\BinaryValue;
use Elabftw\Enums\State;
use Elabftw\Enums\Usergroup;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\Notifications\OnboardingEmail;
use Elabftw\Models\Notifications\SelfIsValidated;
use Elabftw\Models\Notifications\SelfNeedValidation;
use Elabftw\Models\Notifications\UserCreated;
use Elabftw\Models\Notifications\UserNeedValidation;
use Elabftw\Params\UserParams;
use Elabftw\Services\EmailValidator;
use Elabftw\Services\Filter;
use Elabftw\Services\MfaHelper;
use Elabftw\Services\TeamsHelper;
use Elabftw\Services\UserCreator;
use Elabftw\Services\UsersHelper;
use PDO;
use Symfony\Component\HttpFoundation\Request;
use Override;

/**
 * Users
 */
class Users extends AbstractRest
{
    public bool $needValidation = false;

    public array $userData = array();

    public self $requester;

    public bool $isAdmin = false;

    public function __construct(public ?int $userid = null, public ?int $team = null, ?self $requester = null)
    {
        parent::__construct();
        if ($team !== null && $userid !== null) {
            $TeamsHelper = new TeamsHelper($this->team ?? 0);
            $this->isAdmin = $TeamsHelper->isAdmin($userid);
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
        ?Usergroup $usergroup = null,
        bool $automaticValidationEnabled = false,
        bool $alertAdmin = true,
        ?string $validUntil = null,
        ?string $orgid = null,
        bool $allowTeamCreation = false,
        bool $skipDomainValidation = false,
    ): int {
        $Config = Config::getConfig();
        $Teams = new Teams($this);

        // make sure that all the teams in which the user will be are created/exist
        // this might throw an exception if the team doesn't exist and we can't create it on the fly
        $teams = $Teams->getTeamsFromIdOrNameOrOrgidArray($teams, $allowTeamCreation);
        $TeamsHelper = new TeamsHelper($teams[0]['id']);

        $EmailValidator = new EmailValidator($email, (bool) $Config->configArr['admins_import_users'], $Config->configArr['email_domain'], skipDomainValidation: $skipDomainValidation);
        $EmailValidator->validate();

        $firstname = Filter::toPureString($firstname);
        $lastname = Filter::toPureString($lastname);

        // get the user group for the new users
        $usergroup ??= $TeamsHelper->getGroup();

        $isSysadmin = $usergroup === Usergroup::Sysadmin;

        // is user validated automatically (true) or by an admin (false)?
        $isValidated = $automaticValidationEnabled || !$Config->configArr['admin_validate'] || $usergroup !== Usergroup::User;

        $defaultRead = BasePermissions::Team->toJson();
        $defaultWrite = BasePermissions::User->toJson();

        $sql = 'INSERT INTO users (
            `email`,
            `password_hash`,
            `firstname`,
            `lastname`,
            `validated`,
            `lang`,
            `valid_until`,
            `orgid`,
            `is_sysadmin`,
            `default_read`,
            `default_write`,
            `last_seen_version`
        ) VALUES (
            :email,
            :password_hash,
            :firstname,
            :lastname,
            :validated,
            :lang,
            :valid_until,
            :orgid,
            :is_sysadmin,
            :default_read,
            :default_write,
            :last_seen_version);';
        $req = $this->Db->prepare($sql);

        $req->bindParam(':email', $email);
        $req->bindParam(':password_hash', $passwordHash);
        $req->bindParam(':firstname', $firstname);
        $req->bindParam(':lastname', $lastname);
        $req->bindValue(':validated', $isValidated, PDO::PARAM_INT);
        $req->bindValue(':lang', $Config->configArr['lang']);
        $req->bindValue(':valid_until', $validUntil);
        $req->bindValue(':orgid', $orgid);
        $req->bindValue(':is_sysadmin', $isSysadmin, PDO::PARAM_INT);
        $req->bindValue(':default_read', $defaultRead);
        $req->bindValue(':default_write', $defaultWrite);
        $req->bindValue(':last_seen_version', App::INSTALLED_VERSION_INT);
        $this->Db->execute($req);
        $userid = $this->Db->lastInsertId();

        // check if the team is empty before adding the user to the team
        $isFirstUser = $TeamsHelper->isFirstUserInTeam();
        // now add the user to the team
        $Users2Teams = new Users2Teams($this->requester);
        // only send onboarding emails for new teams when user is validated
        if ($isValidated) {
            // do we send an email for the instance
            if ($Config->configArr['onboarding_email_active'] === '1') {
                $isAdmin = $usergroup === Usergroup::Admin || $usergroup === Usergroup::Sysadmin;
                (new OnboardingEmail(-1, $isAdmin))->create($userid);
            }
            // send email for each team
            $Users2Teams->sendOnboardingEmailOfTeams = true;
        }
        $Users2Teams->addUserToTeams(
            $userid,
            array_column($teams, 'id'),
            // transform Sysadmin to Admin because users2teams.is_admin is 1 (Admin) or 0 (User)
            ($usergroup === Usergroup::Sysadmin || $usergroup === Usergroup::Admin)
                ? BinaryValue::True
                : BinaryValue::False,
        );
        if ($alertAdmin && !$isFirstUser) {
            $this->notifyAdmins($TeamsHelper->getAllAdminsUserid(), $userid, $isValidated, $teams[0]['name']);
        }
        if (!$isValidated) {
            $Notifications = new SelfNeedValidation();
            $Notifications->create($userid);
            // set a flag to show correct message to user
            $this->needValidation = true;
        }
        AuditLogs::create(new UserRegister($this->requester->userid ?? 0, $userid));
        return $userid;
    }

    /**
     * Search users based on query. It searches in email, firstname, lastname
     *
     * @param string $query the searched term
     * @param int $teamId limit search to a given team or search all teams if 0
     */
    public function readFromQuery(
        string $query = '',
        int $teamId = 0,
        bool $onlyAdmins = false,
        bool $onlyArchived = false,
        bool $onlyActive = false,
    ): array {
        $teamFilterSql = '';
        if ($teamId > 0) {
            $teamFilterSql = 'JOIN users2teams AS u2t_filter
                              ON u2t_filter.users_id = u.userid
                              AND u2t_filter.teams_id = :team';
        }

        $archived = '';
        if ($onlyArchived) {
            $archived = 'AND u2t_all.is_archived = 1';
        }
        if ($onlyActive) {
            $archived = 'AND u2t_all.is_archived = 0';
        }

        $admins = '';
        if ($onlyAdmins) {
            $admins = 'AND u2t_all.is_admin = 1';
        }

        // NOTE: $tmpTable avoids the use of DISTINCT, so we are able to use ORDER BY with teams_id.
        // Side effect: User is shown in team with lowest id
        $sql = "SELECT
          u.userid,
          u.firstname,
          u.lastname,
          u.created_at,
          u.orgid,
          u.email,
          (u.mfa_secret IS NOT NULL)       AS has_mfa_enabled,
          u.validated,
          u.last_login,
          u.valid_until,
          u.is_sysadmin,
          CONCAT(u.firstname, ' ', u.lastname) AS fullname,
          CONCAT(
            LEFT(IFNULL(u.firstname, 'Anonymous'),  1),
            LEFT(IFNULL(u.lastname,  'Anonymous'),  1)
          ) AS initials,
          u.orcid,
          u.validated,
          u.auth_service,
          sk.pubkey                         AS sig_pubkey,
          JSON_ARRAYAGG(
             JSON_OBJECT(
               'id',   u2t_all.teams_id,
               'name', t.name,
               'is_admin', u2t_all.is_admin,
               'is_owner', u2t_all.is_owner,
               'is_archived', u2t_all.is_archived
             )
           ) AS teams

        FROM users AS u

        LEFT JOIN sig_keys AS sk
          ON sk.userid = u.userid
          AND sk.state  = :state

        " . $teamFilterSql . '

        LEFT JOIN users2teams AS u2t_all
          ON u2t_all.users_id = u.userid

        LEFT JOIN teams AS t
          ON t.id = u2t_all.teams_id

        WHERE
          (u.email      LIKE :query
           OR u.firstname LIKE :query
           OR u.lastname  LIKE :query)
        ' . $admins . ' ' . $archived . '

        GROUP BY
          u.userid,
          u.firstname,
          u.lastname,
          u.created_at,
          u.orgid,
          u.email,
          has_mfa_enabled,
          u.validated,
          u.last_login,
          u.valid_until,
          u.is_sysadmin,
          fullname,
          initials,
          u.orcid,
          u.validated,
          u.auth_service,
          sk.pubkey

        ORDER BY
          MIN(u2t_all.teams_id) ASC,
          u.lastname       ASC;';

        $req = $this->Db->prepare($sql);
        $req->bindValue(':query', '%' . $query . '%');
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        if ($teamId > 0) {
            $req->bindValue(':team', $teamId);
        }
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function readAllFromTeam(): array
    {
        return $this->readFromQuery(teamId: $this->userData['team']);
    }

    public function readAllActiveFromTeam(): array
    {
        return $this->readFromQuery(teamId: $this->userData['team'], onlyActive: true);
    }

    /**
     * This can be called from api and only contains "safe" values
     */
    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $Request = Request::createFromGlobals();

        $team = $Request->query->getInt('team', 0);
        $currentTeam = $Request->query->getInt('currentTeam');
        if ($currentTeam === 1) {
            $team = $this->requester->team ?? 0;
        }
        return $this->readFromQuery(
            $Request->query->getString('q'),
            $team,
            $Request->query->getBoolean('onlyAdmins'),
            $Request->query->getBoolean('onlyArchived'),
        );
    }

    /**
     * This can be called from api and only contains "safe" values
     */
    #[Override]
    public function readOne(): array
    {
        $this->canReadOrExplode();
        $userData = $this->readOneFull();
        unset($userData['password']);
        unset($userData['password_hash']);
        unset($userData['salt']);
        unset($userData['mfa_secret']);
        unset($userData['token']);
        // keep sig_privkey in response if requester is target
        if ($this->requester->userData['userid'] !== $this->userData['userid']) {
            unset($userData['sig_privkey']);
        }
        return $userData;
    }

    public function readNamesFromIds(array $idArr): array
    {
        if (empty($idArr)) {
            return array();
        }
        $onlyIds = array_map('intval', $idArr);
        $sql = "SELECT CONCAT(users.firstname, ' ', users.lastname) AS fullname, userid, email FROM users WHERE userid IN (" . implode(',', $onlyIds) . ') ORDER BY fullname ASC';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function isAdminSomewhere(): bool
    {
        // TODO use the existing userData instead of making a query
        $sql = 'SELECT users_id FROM users2teams WHERE users_id = :userid AND is_admin = 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->rowCount() >= 1;
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        $Creator = new UserCreator($this->requester, $reqBody);
        return $Creator->create();
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        $this->canWriteOrExplode($action);
        match ($action) {
            Action::Add => (
                function () use ($params) {
                    // check instance config if admins are allowed to do that (if requester is not sysadmin)
                    $Config = Config::getConfig();
                    $hasPermission = $this->requester->userData['is_sysadmin'] === 1 || $this->requester->userData['can_manage_users2teams'] === 1;
                    if (!$hasPermission && $Config->configArr['admins_import_users'] === '0') {
                        throw new IllegalActionException('Adding a user in your team is disabled at the instance level (config: admins_import_users)');
                    }
                    // need to be admin to "import" a user in a team
                    $team = (int) ($params['team'] ?? $this->requester->userData['team']);
                    $TeamsHelper = new TeamsHelper($team);
                    $isAdmin = $TeamsHelper->isAdmin($this->requester->userData['userid']);
                    if (!$hasPermission && $isAdmin === false) {
                        throw new IllegalActionException('Only Admin can add a user to a team (where they are Admin)');
                    }
                    $Users2Teams = new Users2Teams($this->requester);
                    if ($this->userData['validated']) {
                        $Users2Teams->sendOnboardingEmailOfTeams = true;
                    }
                    $Users2Teams->create($this->userData['userid'], $team);
                }
            )(),
            Action::Disable2fa => $this->disable2fa(),
            Action::PatchUser2Team => (new Users2Teams($this->requester))->patchUser2Team($params, $this->userid ?? 0),
            Action::Unreference => (new Users2Teams($this->requester))->destroy($this->userData['userid'], (int) $params['team']),
            Action::UpdatePassword => $this->updatePassword($params),
            Action::Update => (
                function () use ($params) {
                    // only a sysadmin can edit anything about another sysadmin
                    if ($this->requester->userData['is_sysadmin'] === 0 && $this->userid !== $this->requester->userid && $this->userData['is_sysadmin'] === 1) {
                        throw new IllegalActionException('A sysadmin level account is required to edit another sysadmin account.');
                    }
                    $Config = Config::getConfig();
                    foreach ($params as $target => $content) {
                        // prevent modification of identity fields if we are not sysadmin
                        if (in_array($target, array('email', 'firstname', 'lastname', 'orgid'), true)
                            && $Config->configArr['allow_users_change_identity'] === '0'
                            && $this->requester->userData['is_sysadmin'] === 0
                        ) {
                            throw new ImproperActionException('Identity information can only be modified by Sysadmin.');
                        }
                        $this->update(new UserParams($target, (string) $content));
                    }
                }
            )(),
            Action::Validate => $this->validate(),
            default => throw new ImproperActionException('Invalid action parameter.'),
        };
        // TODO check when admin if unreference doesn't cause issue
        return $this->readOne();
    }

    #[Override]
    public function getApiPath(): string
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
    #[Override]
    public function destroy(): bool
    {
        $this->canWriteOrExplode();

        $UsersHelper = new UsersHelper($this->userData['userid']);
        if ($UsersHelper->cannotBeDeleted()) {
            throw new ImproperActionException('Cannot delete a user that owns experiments, items, comments, templates or uploads!');
        }

        // Due to the InnoDB cascading actions of the foreign key constraints the deletion will also happen in the other tables
        $sql = 'DELETE FROM users WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        $res = $this->Db->execute($req);
        if ($res) {
            AuditLogs::create(new UserDeleted($this->requester->userid ?? 0, $this->userid ?? 0));
        }
        return $res;
    }

    /**
     * Check if this instance's user is admin of the userid in function argument
     */
    public function isAdminOf(int $userid): bool
    {
        // consider that we are admin of ourselves and that if you have can_manage_users2teams you're kinda an Admin of the user
        if ($this->userid === $userid || $this->userData['is_sysadmin'] === 1 || $this->userData['can_manage_users2teams']) {
            return true;
        }
        // check if in the teams we have in common, the potential admin is admin
        $sql = 'SELECT *
                FROM users2teams u1
                INNER JOIN users2teams u2
                    ON (u1.teams_id = u2.teams_id)
                WHERE u1.users_id = :admin_userid
                    AND u2.users_id = :user_userid
                    AND u1.is_admin = 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':admin_userid', $this->userid, PDO::PARAM_INT);
        $req->bindParam(':user_userid', $userid, PDO::PARAM_INT);
        $req->execute();
        return $req->rowCount() >= 1;
    }

    /**
     * For when password must be different than older one
     * Here the happy path is in the catch... Not great, not terrible...
     */
    public function requireResetPassword(string $password): bool
    {
        $LocalAuth = new Local($this->userData['email'], $password);
        try {
            $LocalAuth->tryAuth();
        } catch (InvalidCredentialsException) {
            return $this->updatePassword(array('password' => $password), true);
        }
        throw new ImproperActionException(_('New password must not be the same as the current one.'));
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
        // set a high maxLoginAttempts because we're just trying to match current password here
        $LocalAuth = new Local($this->userData['email'], $currentPassword, maxLoginAttempts: 999);
        try {
            $LocalAuth->tryAuth();
        } catch (InvalidCredentialsException) {
            throw new ImproperActionException('The current password is not valid!');
        }
    }

    public function update(UserParams $params): bool
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
            Filter::email($params->getStringContent());
        }

        // special case for is_sysadmin: only a sysadmin can affect this column
        if ($params->getTarget() === 'is_sysadmin') {
            if ($this->requester->userData['is_sysadmin'] === 0) {
                throw new IllegalActionException('Non sysadmin user tried to edit the is_sysadmin column of a user');
            }
        }

        // early bail out if existing and new values are the same
        if ($params->getContent() === $this->userData[$params->getColumn()]) {
            return true;
        }

        $sql = 'UPDATE users SET ' . $params->getColumn() . ' = :content WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $params->getContent());
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        $res = $this->Db->execute($req);

        $auditLoggableTargets = array(
            'valid_until',
            'email',
            'orgid',
            'is_sysadmin',
        );

        if ($res
            && in_array($params->getTarget(), $auditLoggableTargets, true)
            && (string) $this->userData[$params->getTarget()] !== $params->getStringContent()
        ) {
            AuditLogs::create(new UserAttributeChanged(
                $this->requester->userid ?? 0,
                $this->userid ?? 0,
                $params->getTarget(),
                (string) $this->userData[$params->getTarget()],
                $params->getStringContent(),
            ));
        }
        return $res;
    }

    protected static function search(string $column, string $term, bool $validated = false): self
    {
        $Db = Db::getConnection();
        $sql = sprintf(
            'SELECT userid FROM users WHERE %s = :term %s LIMIT 1',
            $column === 'orgid'
                ? 'orgid'
                : 'email',
            $validated
                ? 'AND validated = 1'
                : '',
        );
        $req = $Db->prepare($sql);
        $req->bindParam(':term', $term);
        $Db->execute($req);
        $res = (int) $req->fetchColumn();
        if ($res === 0) {
            throw new ResourceNotFoundException();
        }
        return new self($res);
    }

    /**
     * Read all the columns (including sensitive ones) of the current user
     */
    protected function readOneFull(): array
    {
        $sql = "SELECT u.*, sig_keys.privkey AS sig_privkey, sig_keys.pubkey AS sig_pubkey,
            CONCAT(u.firstname, ' ', u.lastname) AS fullname,
            CONCAT(
                LEFT(IFNULL(u.firstname, 'Anonymous'), 1),
                LEFT(IFNULL(u.lastname, 'Anonymous'), 1)
            ) AS initials,
          (u.mfa_secret IS NOT NULL)       AS has_mfa_enabled,
          JSON_ARRAYAGG(
             JSON_OBJECT(
               'id',   u2t.teams_id,
               'name', t.name,
               'is_admin', u2t.is_admin,
               'is_owner', u2t.is_owner,
               'is_archived', u2t.is_archived
             )
           ) AS teams
            FROM users AS u
            LEFT JOIN sig_keys ON (sig_keys.userid = u.userid AND state = :state)
            LEFT JOIN users2teams AS u2t
              ON u2t.users_id = :userid
            LEFT JOIN teams AS t
              ON t.id = u2t.teams_id
            WHERE u.userid = :userid
            GROUP BY
              u.userid,
              u.firstname,
              u.lastname,
              u.created_at,
              u.orgid,
              u.email,
              u.validated,
              u.last_login,
              u.valid_until,
              u.is_sysadmin,
              fullname,
              initials,
              u.orcid,
              u.validated,
              u.auth_service,
              sig_keys.pubkey,
              sig_keys.privkey";
        $req = $this->Db->prepare($sql);
        $req->bindValue(':userid', $this->userid, PDO::PARAM_INT);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $this->Db->execute($req);

        $this->userData = $this->Db->fetch($req);
        $this->userData['team'] = $this->team;
        return $this->userData;
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
        $sql = 'UPDATE users SET password_hash = :content, password_modified_at = NOW() WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $params->getContent());
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        $res = $this->Db->execute($req);
        AuditLogs::create(new PasswordChanged(
            $this->requester->userid ?? 0,
            $this->userid ?? 0,
            'password',
            'the old password',
            'the new password',
        ));
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
        // if you have can_manage_users2teams you can add/unreference user
        if (($action === Action::Add || $action === Action::Unreference) && $this->requester->userData['can_manage_users2teams'] === 1) {
            return;
        }
        if (!$this->requester->isAdminOf($this->userData['userid']) && $action !== Action::Add) {
            throw new IllegalActionException();
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
        $this->sendOnboardingEmailsAfterValidation();
        return $this->readOne();
    }

    private function notifyAdmins(array $admins, int $userid, bool $isValidated, string $team): void
    {
        $Notifications = new UserCreated($userid, $team);
        if (!$isValidated) {
            $Notifications = new UserNeedValidation($userid, $team);
        }
        foreach ($admins as $admin) {
            $Notifications->create($admin);
        }
    }

    private function sendOnboardingEmailsAfterValidation(): void
    {
        // do we send an eamil for the instance
        if (Config::getConfig()->configArr['onboarding_email_active'] === '1') {
            (new OnboardingEmail(-1, $this->isAdmin))->create($this->userData['userid']);
        }

        // Check setting for each team individually
        $teams = json_decode($this->userData['teams']);
        foreach (array_column($teams, 'id') as $teamId) {
            if ((new Teams($this, $this->team))->readOne()['onboarding_email_active'] === 1) {
                (new OnboardingEmail($teamId))->create($this->userData['userid']);
            }
        }
    }
}
