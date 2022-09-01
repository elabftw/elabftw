<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use function array_filter;
use Elabftw\Elabftw\CreateNotificationParams;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Tools;
use Elabftw\Elabftw\UserParams;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Services\Check;
use Elabftw\Services\EmailValidator;
use Elabftw\Services\Filter;
use Elabftw\Services\TeamsHelper;
use Elabftw\Services\UserArchiver;
use Elabftw\Services\UserCreator;
use Elabftw\Services\UsersHelper;
use function filter_var;
use function password_hash;
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

    protected Db $Db;

    public function __construct(public ?int $userid = null, ?int $team = null, public ?self $requester = null)
    {
        $this->Db = Db::getConnection();
        if ($team !== null) {
            $this->team = $team;
        }
        if ($userid !== null) {
            $this->readOneFull();
        }
    }

    /**
     * Create a new user
     */
    public function createOne(
        string $email,
        array $teams,
        string $firstname = '',
        string $lastname = '',
        string $password = '',
        ?int $group = null,
        bool $forceValidation = false,
        bool $alertAdmin = true,
    ): int {
        $Config = Config::getConfig();
        $Teams = new Teams($this);

        // make sure that all the teams in which the user will be are created/exist
        // this might throw an exception if the team doesn't exist and we can't create it on the fly
        $teams = $Teams->getTeamsFromIdOrNameOrOrgidArray($teams);
        $TeamsHelper = new TeamsHelper((int) $teams[0]['id']);

        $EmailValidator = new EmailValidator($email, $Config->configArr['email_domain']);
        $EmailValidator->validate();

        if ($password !== '') {
            Check::passwordLength($password);
        }

        $firstname = filter_var($firstname, FILTER_SANITIZE_STRING);
        $lastname = filter_var($lastname, FILTER_SANITIZE_STRING);

        // Create password hash
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

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


        $sql = 'INSERT INTO users (
            `email`,
            `password_hash`,
            `firstname`,
            `lastname`,
            `usergroup`,
            `register_date`,
            `validated`,
            `lang`
        ) VALUES (
            :email,
            :password_hash,
            :firstname,
            :lastname,
            :usergroup,
            :register_date,
            :validated,
            :lang);';
        $req = $this->Db->prepare($sql);

        $req->bindParam(':email', $email);
        $req->bindParam(':password_hash', $passwordHash);
        $req->bindParam(':firstname', $firstname);
        $req->bindParam(':lastname', $lastname);
        $req->bindParam(':register_date', $registerDate);
        $req->bindParam(':validated', $validated, PDO::PARAM_INT);
        $req->bindParam(':usergroup', $group, PDO::PARAM_INT);
        $req->bindValue(':lang', $Config->configArr['lang']);
        $this->Db->execute($req);
        $userid = $this->Db->lastInsertId();

        // now add the user to the team
        $Users2Teams = new Users2Teams();
        $Users2Teams->addUserToTeams($userid, array_column($teams, 'id'));
        if ($alertAdmin && !$TeamsHelper->isFirstUserInTeam()) {
            $this->notifyAdmins($TeamsHelper->getAllAdminsUserid(), $userid, $validated);
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
     * Get users matching a search term for consumption in autocomplete
     */
    public function read(ContentParamsInterface $params): array
    {
        $usersArr = array_filter($this->readFromQuery($params->getContent()), function ($u) {
            return ((int) $u['archived']) === 0;
        });
        $res = array();
        foreach ($usersArr as $user) {
            $res[] = $user['userid'] . ' - ' . $user['fullname'] . ' - ' . $user['email'];
        }
        return $res;
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

        // NOTE: $tmpTable avoids the use of DISTINCT, so we are able to use ORDER BY with teams_id.
        // Side effect: User is shown in team with lowest id
        $sql = "SELECT users.userid,
            users.firstname, users.lastname, users.email, users.mfa_secret,
            users.validated, users.usergroup, users.archived, users.last_login,
            CONCAT(users.firstname, ' ', users.lastname) AS fullname,
            users.orcid, users.auth_service
            FROM users
            CROSS JOIN" . $tmpTable . ' users2teams ON (users2teams.users_id = users.userid' . $teamFilterSql . ')
            WHERE (users.email LIKE :query OR users.firstname LIKE :query OR users.lastname LIKE :query)
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
     * @psalm-suppress PossiblyNullArgument
     * @psalm-suppress PossiblyNullArrayAccess
     * @psalm-suppress PossiblyNullPropertyFetch
     */
    public function readAllFromTeam(): array
    {
        if ($this->requester->userData['is_admin'] !== 1) {
            throw new IllegalActionException('Only admin can read all users from a team.');
        }
        return $this->readFromQuery('', $this->userData['team']);
    }

    /** @psalm-suppress PossiblyNullArgument
        @psalm-suppress PossiblyNullArrayAccess
        @psalm-suppress PossiblyNullPropertyFetch */
    public function readAll(): array
    {
        $Request = Request::createFromGlobals();
        if ($this->requester->userData['is_sysadmin'] === 1) {
            return $this->readFromQuery($Request->query->getAlnum('q'));
        }
        if ($this->requester->userData['is_admin'] === 1) {
            return $this->readFromQuery($Request->query->getAlnum('q'), $this->requester->userData['team']);
        }
        throw new IllegalActionException('Normal users cannot read other users.');
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

    public function postAction(Action $action, array $reqBody): int
    {
        /** @psalm-suppress PossiblyNullArgument */
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
                    if ($this->requester instanceof self) {
                        $isSysadmin = $this->requester->userData['is_sysadmin'];
                    }
                    foreach ($params as $target => $content) {
                        $this->update(new UserParams($target, (string) $content, $isSysadmin));
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
        $sql = 'DELETE FROM users WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        return $this->Db->execute($req);
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

    private function canReadOrExplode(): void
    {
        if ($this->requester === null || $this->requester->userid === $this->userid) {
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
        if ($this->requester === null) {
            return;
        }
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
            groups.is_admin, groups.is_sysadmin
            FROM users
            LEFT JOIN `groups` ON groups.id = users.usergroup
            WHERE users.userid = :userid";
        $req = $this->Db->prepare($sql);
        $req->bindValue(':userid', $this->userid, PDO::PARAM_INT);
        $this->Db->execute($req);

        $this->userData = $this->Db->fetch($req);
        $this->userData['team'] = $this->team;
        return $this->userData;
    }

    private function notifyAdmins(array $admins, int $userid, int $validated): void
    {
        $body = array(
            'userid' => $userid,
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
