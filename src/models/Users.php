<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Auth;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\Email;
use PDO;

/**
 * Users
 */
class Users
{
    /** @var Auth $Auth instance of Auth */
    public $Auth;

    /** @var Config $Config instance of Config */
    public $Config;

    /** @var bool $needValidation flag to check if we need validation or not */
    public $needValidation = false;

    /** @var array $userData what you get when you read() */
    public $userData = array();

    /** @var Db $Db SQL Database */
    protected $Db;

    /**
     * Constructor
     *
     * @param int|null $userid
     * @param Auth|null $auth
     * @param Config|null $config
     */
    public function __construct(?int $userid = null, ?Auth $auth = null, ?Config $config = null)
    {
        $this->Db = Db::getConnection();
        if ($userid !== null) {
            $this->setId($userid);
        }

        if ($auth instanceof Auth) {
            $this->Auth = $auth;
        }
        if ($config instanceof Config) {
            $this->Config = $config;
        }
    }

    /**
     * Assign an id and populate userData
     *
     * @param int $userid
     */
    public function setId(int $userid): void
    {
        if (Tools::checkId($userid) === false) {
            throw new ImproperActionException('Bad userid');
        }
        $this->userData = $this->read($userid);
    }

    /**
     * Create a new user. If no password is provided, it's because we create it from SAML.
     *
     * @param string $email
     * @param int $team
     * @param string $firstname
     * @param string $lastname
     * @param string $password
     * @return void
     */
    public function create(string $email, int $team, string $firstname = '', string $lastname = '', string $password = ''): void
    {
        // check for duplicate of email
        if ($this->isDuplicateEmail($email)) {
            throw new ImproperActionException(_('Someone is already using that email address!'));
        }

        if ($password) {
            $this->Auth->checkPasswordLength($password);
        }

        $firstname = \filter_var($firstname, FILTER_SANITIZE_STRING);
        $lastname = \filter_var($lastname, FILTER_SANITIZE_STRING);

        // Create salt
        $salt = \hash('sha512', \bin2hex(\random_bytes(16)));
        // Create hash
        $passwordHash = \hash('sha512', $salt . $password);

        // Registration date is stored in epoch
        $registerDate = \time();

        // get the group for the new user
        $group = $this->getGroup($team);

        // will new user be validated?
        $validated = $this->getValidated($group);

        $sql = 'INSERT INTO users (
            `email`,
            `password`,
            `firstname`,
            `lastname`,
            `team`,
            `usergroup`,
            `salt`,
            `register_date`,
            `validated`,
            `lang`
        ) VALUES (
            :email,
            :password,
            :firstname,
            :lastname,
            :team,
            :usergroup,
            :salt,
            :register_date,
            :validated,
            :lang);';
        $req = $this->Db->prepare($sql);

        $req->bindParam(':email', $email);
        $req->bindParam(':team', $team, PDO::PARAM_INT);
        $req->bindParam(':salt', $salt);
        $req->bindParam(':password', $passwordHash);
        $req->bindParam(':firstname', $firstname);
        $req->bindParam(':lastname', $lastname);
        $req->bindParam(':register_date', $registerDate);
        $req->bindParam(':validated', $validated);
        $req->bindParam(':usergroup', $group);
        $req->bindValue(':lang', $this->Config->configArr['lang']);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        if ($validated == '0') {
            $Email = new Email($this->Config, $this);
            $Email->alertAdmin($team);
            // set a flag to show correct message to user
            $this->needValidation = true;
        }
    }

    /**
     * Check we have not a duplicate email in DB
     *
     * @param string $email
     * @return bool true if there is a duplicate
     */
    public function isDuplicateEmail(string $email): bool
    {
        $sql = 'SELECT email FROM users WHERE email = :email AND archived = 0';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':email', $email);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        return (bool) $req->rowCount();
    }

    /**
     * Get info about a user
     *
     * @param int $userid
     * @return array
     */
    public function read(int $userid): array
    {
        $sql = "SELECT users.*, CONCAT(users.firstname, ' ', users.lastname) AS fullname,
            groups.can_lock, groups.is_admin, groups.is_sysadmin FROM users
            LEFT JOIN `groups` ON groups.id = users.usergroup
            WHERE users.userid = :userid";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
        $res = $req->fetch();
        if ($res === false) {
            throw new IllegalActionException('User not found.');
        }

        return $res;
    }

    /**
     * Select by email
     *
     * @param string $email
     * @return void
     */
    public function populateFromEmail(string $email): void
    {
        $sql = 'SELECT userid
            FROM users
            WHERE email = :email AND archived = 0 LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':email', $email);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
        $res = $req->fetchColumn();
        if ($res === false) {
            throw new ImproperActionException(_('Email not found in database!'));
        }
        $this->setId((int) $res);
    }

    /**
     * Search users based on query. It searches in email, firstname, lastname or team name
     *
     * @param string $query the searched term
     * @param bool $teamFilter toggle between sysadmin/admin view
     * @return array
     */
    public function readFromQuery(string $query, bool $teamFilter = false): array
    {
        $whereTeam = '';
        if ($teamFilter) {
            $whereTeam = 'users.team = ' . $this->userData['team'] . ' AND ';
        }

        $sql = 'SELECT users.userid,
            users.firstname, users.lastname, users.team, users.email,
            users.validated, users.usergroup, users.archived, users.last_login,
            teams.name as teamname
            FROM users
            LEFT JOIN teams ON (users.team = teams.id)
            WHERE ' . $whereTeam . ' (users.email LIKE :query OR users.firstname LIKE :query OR users.lastname LIKE :query OR teams.name LIKE :query)
            ORDER BY users.team ASC, users.usergroup ASC, users.lastname ASC';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':query', '%' . $query . '%');
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
    }

    /**
     * Read all users from the team
     *
     * @param int|null $validated
     * @return array
     */
    public function readAllFromTeam(?int $validated = null): array
    {
        $valSql = '';
        if (is_int($validated)) {
            $valSql = ' users.validated = :validated AND ';
        }
        $sql = "SELECT users.*, CONCAT (users.firstname, ' ', users.lastname) AS fullname,
            teams.name AS teamname
            FROM users
            LEFT JOIN teams ON (users.team = teams.id)
            WHERE " . $valSql . ' users.team = :team';
        $req = $this->Db->prepare($sql);
        if (is_int($validated)) {
            $req->bindValue(':validated', $validated);
        }
        $req->bindValue(':team', $this->userData['team']);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
    }

    /**
     * Get email for every single user
     *
     * @param bool $fromTeam
     * @return array
     */
    public function getAllEmails(bool $fromTeam = false): array
    {
        $sql = 'SELECT email FROM users WHERE validated = 1 AND archived = 0';
        if ($fromTeam) {
            $sql .= ' AND team = :team';
        }
        $req = $this->Db->prepare($sql);
        if ($fromTeam) {
            $req->bindParam(':team', $this->userData['team'], PDO::PARAM_INT);
        }
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
    }

    /**
     * Update user from the editusers template
     *
     * @param array $params POST
     * @return void
     */
    public function update(array $params): void
    {
        $firstname = filter_var($params['firstname'], FILTER_SANITIZE_STRING);
        $lastname = filter_var($params['lastname'], FILTER_SANITIZE_STRING);
        $email = filter_var($params['email'], FILTER_SANITIZE_EMAIL);
        $team = Tools::checkId((int) $params['team']);
        if ($this->hasExperiments((int) $this->userData['userid']) && $team !== (int) $this->userData['team']) {
            throw new ImproperActionException('You are trying to change the team of a user with existing experiments. You might want to Archive this user instead!');
        }

        // check email is not already in db
        $usersEmails = $this->getAllEmails();
        $emailsArr = array();
        // get all emails in a nice array
        foreach ($usersEmails as $user) {
            $emailsArr[] = $user['email'];
        }

        // now make sure the new email is not already used by someone
        // it's okay if it's the same email as before though
        if (\in_array($email, $emailsArr, true) && $email !== $this->userData['email']) {
            throw new ImproperActionException('Email is already used by non archived user!');
        }

        if ($params['validated'] == 1) {
            $validated = 1;
        } else {
            $validated = 0;
        }
        $usergroup = Tools::checkId((int) $params['usergroup']);
        if ($usergroup === false) {
            throw new IllegalActionException('The id parameter is not valid!');
        }

        // a non sysadmin cannot put someone sysadmin
        if ($usergroup == 1 && $this->Auth->Session->get('is_sysadmin') != 1) {
            throw new ImproperActionException(_('Only a sysadmin can put someone sysadmin.'));
        }

        if (\mb_strlen($params['password']) > 1) {
            $this->updatePassword($params['password']);
        }

        $sql = 'UPDATE users SET
            firstname = :firstname,
            lastname = :lastname,
            email = :email,
            team = :team,
            usergroup = :usergroup,
            validated = :validated
            WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':firstname', $firstname);
        $req->bindParam(':lastname', $lastname);
        $req->bindParam(':email', $email);
        $req->bindParam(':team', $team);
        $req->bindParam(':validated', $validated);
        $req->bindParam(':usergroup', $usergroup);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Update preferences from user control panel
     *
     * @param array $params
     * @return void
     */
    public function updatePreferences(array $params): void
    {
        // LIMIT
        $filter_options = array(
            'options' => array(
                'default' => 15,
                'min_range' => 1,
                'max_range' => 500,
            ), );
        $new_limit = filter_var($params['limit'], FILTER_VALIDATE_INT, $filter_options);

        // ORDER BY
        $new_orderby = null;
        $whitelistOrderby = array(null, 'cat', 'date', 'title', 'comment');
        if (isset($params['orderby']) && \in_array($params['orderby'], $whitelistOrderby, true)) {
            $new_orderby = $params['orderby'];
        }

        // SORT
        $new_sort = 'desc';
        if (isset($params['sort']) && ($params['sort'] === 'asc' || $params['sort'] === 'desc')) {
            $new_sort = $params['sort'];
        }

        // LAYOUT
        $new_layout = 0;
        if (isset($params['single_column_layout']) && $params['single_column_layout'] === 'on') {
            $new_layout = 1;
        }

        // KEYBOARD SHORTCUTS
        // only take first letter
        $new_sc_create = $params['sc_create'][0];
        if (!ctype_alpha($new_sc_create)) {
            $new_sc_create = 'c';
        }
        $new_sc_edit = $params['sc_edit'][0];
        if (!ctype_alpha($new_sc_edit)) {
            $new_sc_edit = 'e';
        }
        $new_sc_submit = $params['sc_submit'][0];
        if (!ctype_alpha($new_sc_submit)) {
            $new_sc_submit = 's';
        }
        $new_sc_todo = $params['sc_todo'][0];
        if (!ctype_alpha($new_sc_todo)) {
            $new_sc_todo = 't';
        }

        // SHOW TEAM
        $new_show_team = 0;
        if (isset($params['show_team']) && $params['show_team'] === 'on') {
            $new_show_team = 1;
        }

        // CLOSE WARNING
        $new_close_warning = 0;
        if (isset($params['close_warning']) && $params['close_warning'] === 'on') {
            $new_close_warning = 1;
        }

        // CJK FONTS
        $new_cjk_fonts = 0;
        if (isset($params['cjk_fonts']) && $params['cjk_fonts'] === 'on') {
            $new_cjk_fonts = 1;
        }

        // PDF/A
        $new_pdfa = 0;
        if (isset($params['pdfa']) && $params['pdfa'] === 'on') {
            $new_pdfa = 1;
        }

        // PDF format
        $new_pdf_format = 'A4';
        $formatsArr = array('A4', 'LETTER', 'ROYAL');
        if (\in_array($params['pdf_format'], $formatsArr, true)) {
            $new_pdf_format = $params['pdf_format'];
        }

        // USE MARKDOWN
        $new_use_markdown = 0;
        if (isset($params['use_markdown']) && $params['use_markdown'] === 'on') {
            $new_use_markdown = 1;
        }

        // INCLUDE FILES IN PDF
        $new_inc_files_pdf = 0;
        if (isset($params['inc_files_pdf']) && $params['inc_files_pdf'] === 'on') {
            $new_inc_files_pdf = 1;
        }

        // CHEM EDITOR
        $new_chem_editor = 0;
        if (isset($params['chem_editor']) && $params['chem_editor'] === 'on') {
            $new_chem_editor = 1;
        }

        // LANG
        $new_lang = 'en_GB';
        if (isset($params['lang']) && array_key_exists($params['lang'], Tools::getLangsArr())) {
            $new_lang = $params['lang'];
        }

        // ALLOW EDIT
        $new_allow_edit = 0;
        if (isset($params['allow_edit']) && $params['allow_edit'] === 'on') {
            $new_allow_edit = 1;
        }

        // ALLOW GROUP EDIT
        $new_allow_group_edit = 0;
        if (isset($params['allow_group_edit']) && $params['allow_group_edit'] === 'on') {
            $new_allow_group_edit = 1;
        }

        // DEFAULT VIS
        $new_default_vis = null;
        if (isset($params['default_vis'])) {
            $new_default_vis = Tools::checkVisibility($params['default_vis']);
        }

        // Signature pdf
        // only use cookie here because it's temporary code
        if (isset($params['pdf_sig']) && $params['pdf_sig'] === 'on') {
            \setcookie('pdf_sig', '1', time() + 2592000, '/', '', true, true);
        } else {
            \setcookie('pdf_sig', '0', time() - 3600, '/', '', true, true);
        }

        $sql = 'UPDATE users SET
            limit_nb = :new_limit,
            orderby = :new_orderby,
            sort = :new_sort,
            sc_create = :new_sc_create,
            sc_edit = :new_sc_edit,
            sc_submit = :new_sc_submit,
            sc_todo = :new_sc_todo,
            show_team = :new_show_team,
            close_warning = :new_close_warning,
            chem_editor = :new_chem_editor,
            lang = :new_lang,
            default_vis = :new_default_vis,
            single_column_layout = :new_layout,
            cjk_fonts = :new_cjk_fonts,
            pdfa = :new_pdfa,
            pdf_format = :new_pdf_format,
            use_markdown = :new_use_markdown,
            allow_edit = :new_allow_edit,
            allow_group_edit = :new_allow_group_edit,
            inc_files_pdf = :new_inc_files_pdf
            WHERE userid = :userid;';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':new_limit', $new_limit);
        $req->bindParam(':new_orderby', $new_orderby);
        $req->bindParam(':new_sort', $new_sort);
        $req->bindParam(':new_sc_create', $new_sc_create);
        $req->bindParam(':new_sc_edit', $new_sc_edit);
        $req->bindParam(':new_sc_submit', $new_sc_submit);
        $req->bindParam(':new_sc_todo', $new_sc_todo);
        $req->bindParam(':new_show_team', $new_show_team);
        $req->bindParam(':new_close_warning', $new_close_warning);
        $req->bindParam(':new_chem_editor', $new_chem_editor);
        $req->bindParam(':new_lang', $new_lang);
        $req->bindParam(':new_default_vis', $new_default_vis);
        $req->bindParam(':new_layout', $new_layout);
        $req->bindParam(':new_cjk_fonts', $new_cjk_fonts);
        $req->bindParam(':new_pdfa', $new_pdfa);
        $req->bindParam(':new_pdf_format', $new_pdf_format);
        $req->bindParam(':new_use_markdown', $new_use_markdown);
        $req->bindParam(':new_allow_edit', $new_allow_edit);
        $req->bindParam(':new_allow_group_edit', $new_allow_group_edit);
        $req->bindParam(':new_inc_files_pdf', $new_inc_files_pdf);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Update things from UCP
     *
     * @param array $params
     * @return void
     */
    public function updateAccount(array $params): void
    {
        // check that we got the good password
        if (!$this->Auth->checkCredentials($this->userData['email'], $params['currpass'])) {
            throw new ImproperActionException(_('Please input your current password!'));
        }
        // PASSWORD CHANGE
        if (!empty($params['newpass'])) {
            if ($params['newpass'] != $params['cnewpass']) {
                throw new ImproperActionException(_('The passwords do not match!'));
            }

            $this->updatePassword($params['newpass']);
        }

        $params['firstname'] = filter_var($params['firstname'], FILTER_SANITIZE_STRING);
        $params['lastname'] = filter_var($params['lastname'], FILTER_SANITIZE_STRING);
        $params['email'] = filter_var($params['email'], FILTER_SANITIZE_EMAIL);

        if ($this->isDuplicateEmail($params['email']) && ($params['email'] != $this->userData['email'])) {
            throw new ImproperActionException(_('Someone is already using that email address!'));
        }

        // Check phone
        $params['phone'] = filter_var($params['phone'], FILTER_SANITIZE_STRING);
        // Check cellphone
        $params['cellphone'] = filter_var($params['cellphone'], FILTER_SANITIZE_STRING);
        // Check skype
        $params['skype'] = filter_var($params['skype'], FILTER_SANITIZE_STRING);

        // Check website
        $params['website'] = filter_var($params['website'], FILTER_VALIDATE_URL);

        $sql = 'UPDATE users SET
            email = :email,
            firstname = :firstname,
            lastname = :lastname,
            phone = :phone,
            cellphone = :cellphone,
            skype = :skype,
            website = :website
            WHERE userid = :userid';
        $req = $this->Db->prepare($sql);

        $req->bindParam(':email', $params['email']);
        $req->bindParam(':firstname', $params['firstname']);
        $req->bindParam(':lastname', $params['lastname']);
        $req->bindParam(':phone', $params['phone']);
        $req->bindParam(':cellphone', $params['cellphone']);
        $req->bindParam(':skype', $params['skype']);
        $req->bindParam(':website', $params['website']);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Update the password for the user
     *
     * @param string $password The new password
     * @return void
     */
    public function updatePassword(string $password): void
    {
        $this->Auth->checkPasswordLength($password);

        $salt = \hash('sha512', \bin2hex(\random_bytes(16)));
        $passwordHash = \hash('sha512', $salt . $password);

        $sql = 'UPDATE users SET salt = :salt, password = :password, token = null WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':salt', $salt);
        $req->bindParam(':password', $passwordHash);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Validate current user instance
     *
     * @return void
     */
    public function validate(): void
    {
        $sql = 'UPDATE users SET validated = 1 WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
        // send an email to the user
        $Email = new Email($this->Config, $this);
        $Email->alertUserIsValidated($this->userData['email']);
    }

    /**
     * Check if a user owns experiments
     * This is used to prevent changing the team of a user with experiments
     *
     * @param int $userid the user to check
     * @return bool
     */
    public function hasExperiments(int $userid): bool
    {
        $sql = 'SELECT COUNT(id) FROM experiments WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        return (bool) $req->fetchColumn();
    }

    /**
     * Archive a user
     *
     * @return void
     */
    public function archive(): void
    {
        $sql = 'UPDATE users SET archived = 1, token = null WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        $sql = 'UPDATE experiments
            SET locked = :locked, lockedby = :userid, lockedwhen = CURRENT_TIMESTAMP WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':locked', 1);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Destroy user. Will completely remove everything from the user.
     *
     * @return void
     */
    public function destroy(): void
    {
        $sql = 'DELETE FROM users WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        // remove all experiments from this user
        $sql = 'SELECT id FROM experiments WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userData['userid'], PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
        while ($exp = $req->fetch()) {
            $Experiments = new Experiments($this, (int) $exp['id']);
            $Experiments->destroy();
        }
    }

    /**
     * Get what will be the value of the validated column in users table
     *
     * @param int $group
     * @return int
     */
    private function getValidated(int $group): int
    {
        // validation is required for normal user
        if ($this->Config->configArr['admin_validate'] === '1' && $group === 4) {
            return 0; // so new user will need validation
        }
        return 1;
    }

    /**
     * Return the group int that will be assigned to a new user in a team
     * 1 = sysadmin if it's the first user ever
     * 2 = admin for first user in a team
     * 4 = normal user
     *
     * @param int $team
     * @return int
     */
    private function getGroup(int $team): int
    {
        if ($this->isFirstUser()) {
            return 1;
        }

        if ($this->isFirstUserInTeam($team)) {
            return 2;
        }
        return 4;
    }

    /**
     * Do we have users in the DB ?
     *
     * @return bool
     */
    private function isFirstUser(): bool
    {
        $sql = 'SELECT COUNT(*) AS usernb FROM users';
        $req = $this->Db->prepare($sql);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
        $test = $req->fetch();

        return $test['usernb'] === '0';
    }

    /**
     * Are we the first user to register in a team ?
     *
     * @param int $team
     * @return bool
     */
    private function isFirstUserInTeam(int $team): bool
    {
        $sql = 'SELECT COUNT(*) AS usernb FROM users WHERE team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $team, PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
        $test = $req->fetch();

        return $test['usernb'] === '0';
    }
}
