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
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Services\Check;
use Elabftw\Services\EmailValidator;
use Elabftw\Services\Filter;
use Elabftw\Services\TeamsHelper;
use Elabftw\Services\UsersHelper;
use function filter_var;
use function hash;
use function mb_strlen;
use function password_hash;
use PDO;
use function time;

/**
 * Users
 */
class Users
{
    public bool $needValidation = false;

    public array $userData = array();

    public int $team = 0;

    protected Db $Db;

    public function __construct(?int $userid = null, ?int $team = null)
    {
        $this->Db = Db::getConnection();
        if ($team !== null) {
            $this->team = $team;
        }
        if ($userid !== null) {
            $this->populate($userid);
        }
    }

    /**
     * Populate userData property
     */
    public function populate(int $userid): void
    {
        Check::idOrExplode($userid);
        $this->userData = $this->getUserData($userid);
        $this->userData['team'] = $this->team;
    }

    /**
     * Create a new user
     */
    public function create(
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
            users.cellphone, users.phone, users.website, users.skype
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
     */
    public function readAllFromTeam(): array
    {
        return $this->readFromQuery('', $this->userData['team']);
    }

    public function getLockedUsersCount(): int
    {
        $sql = 'SELECT COUNT(userid) FROM users WHERE allow_untrusted = 0';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    public function update(ContentParamsInterface $params): bool
    {
        $sql = 'UPDATE users SET ' . $params->getTarget() . ' = :content WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $params->getContent());
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Update user from the editusers template
     *
     * @param array<string, mixed> $params POST
     */
    public function updateUser(array $params): bool
    {
        $this->checkEmail($params['email']);

        $firstname = Filter::sanitize($params['firstname']);
        $lastname = Filter::sanitize($params['lastname']);

        // (Sys)admins can only disable 2FA
        // input is disabled if there is no mfa active so no need for an else case
        $mfaSql = '';
        if (isset($params['use_mfa']) && $params['use_mfa'] === 'off') {
            $mfaSql = ', mfa_secret = null';
        }

        $validated = 0;
        if ($params['validated'] === '1') {
            $validated = 1;
        }

        $usergroup = Check::id((int) $params['usergroup']);

        if (mb_strlen($params['password']) > 1) {
            $this->updatePassword($params['password']);
        }

        $sql = 'UPDATE users SET
            firstname = :firstname,
            lastname = :lastname,
            email = :email,
            usergroup = :usergroup,
            validated = :validated';
        $sql .= $mfaSql;
        $sql .= ' WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':firstname', $firstname);
        $req->bindParam(':lastname', $lastname);
        $req->bindParam(':email', $params['email']);
        $req->bindParam(':usergroup', $usergroup);
        $req->bindParam(':validated', $validated);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Update the user's email
     * Note: should only be done if auth method is local!
     */
    public function updateEmail(string $email): bool
    {
        $this->checkEmail($email);
        $sql = 'UPDATE users SET email = :email WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':email', $email, PDO::PARAM_STR);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Update things from UCP
     *
     * @param array<string, mixed> $params
     */
    public function updateAccount(array $params): bool
    {
        $params['firstname'] = Filter::sanitize($params['firstname']);
        $params['lastname'] = Filter::sanitize($params['lastname']);

        // Check phone
        $params['phone'] = filter_var($params['phone'], FILTER_SANITIZE_STRING);
        // Check cellphone
        $params['cellphone'] = filter_var($params['cellphone'], FILTER_SANITIZE_STRING);
        // Check skype
        $params['skype'] = filter_var($params['skype'], FILTER_SANITIZE_STRING);

        // Check website
        $params['website'] = filter_var($params['website'], FILTER_VALIDATE_URL);

        $sql = 'UPDATE users SET
            firstname = :firstname,
            lastname = :lastname,
            phone = :phone,
            cellphone = :cellphone,
            skype = :skype,
            website = :website
            WHERE userid = :userid';
        $req = $this->Db->prepare($sql);

        $req->bindParam(':firstname', $params['firstname']);
        $req->bindParam(':lastname', $params['lastname']);
        $req->bindParam(':phone', $params['phone']);
        $req->bindParam(':cellphone', $params['cellphone']);
        $req->bindParam(':skype', $params['skype']);
        $req->bindParam(':website', $params['website']);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Update the password for the user
     */
    public function updatePassword(string $password): bool
    {
        Check::passwordLength($password);

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $sql = 'UPDATE users SET password_hash = :password_hash, token = null WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':password_hash', $passwordHash);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        return $this->Db->execute($req);
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

    /**
     * Validate current user instance
     */
    public function validate(): bool
    {
        $sql = 'UPDATE users SET validated = 1 WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        $res = $this->Db->execute($req);
        $Notifications = new Notifications($this);
        $Notifications->create(new CreateNotificationParams(Notifications::SELF_IS_VALIDATED));
        return $res;
    }

    /**
     * Archive/Unarchive a user
     */
    public function toggleArchive(): bool
    {
        if ($this->userData['archived']) {
            if ($this->getUnarchivedCount() > 0) {
                throw new ImproperActionException('Cannot unarchive this user because they have another active account with the same email!');
            }
        }

        $sql = 'UPDATE users SET archived = IF(archived = 1, 0, 1), token = null WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Lock all the experiments owned by user
     */
    public function lockExperiments(): bool
    {
        $sql = 'UPDATE experiments
            SET locked = :locked, lockedby = :userid, lockedwhen = CURRENT_TIMESTAMP WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':locked', 1);
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

        if ($res['allow_untrusted'] === '1') {
            return true;
        }
        // check for the time when it was locked
        return $res['currently_locked'] === '0';
    }

    /**
     * Destroy user. Will completely remove everything from the user.
     */
    public function destroy(): bool
    {
        $UsersHelper = new UsersHelper((int) $this->userData['userid']);
        if ($UsersHelper->hasExperiments()) {
            throw new ImproperActionException('Cannot delete a user that owns experiments!');
        }
        $sql = 'DELETE FROM users WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        return $this->Db->execute($req);
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

    // if the user is already archived, make sure there is no other account with the same email
    private function getUnarchivedCount(): int
    {
        $sql = 'SELECT COUNT(email) FROM users WHERE email = :email AND archived = 0';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':email', $this->userData['email']);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    /**
     * Get info about a user
     */
    private function getUserData(int $userid): array
    {
        $sql = "SELECT users.*, CONCAT(users.firstname, ' ', users.lastname) AS fullname,
            groups.is_admin, groups.is_sysadmin FROM users
            LEFT JOIN `groups` ON groups.id = users.usergroup
            WHERE users.userid = :userid";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->Db->fetch($req);
    }

    private function checkEmail(string $email): void
    {
        // do nothing if the email sent is the same as the existing one
        if ($email === $this->userData['email']) {
            return;
        }
        // if the sent email is different from the existing one, check it's valid (not duplicate and respects domain constraint)
        $Config = Config::getConfig();
        $EmailValidator = new EmailValidator($email, $Config->configArr['email_domain']);
        $EmailValidator->validate();
    }
}
