<?php
/**
 * \Elabftw\Elabftw\Users
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use PDO;
use Exception;
use Swift_Message;

/**
 * Users
 */
class Users extends Auth
{
    /** instance of Config */
    public $Config;

    /** flag to check if we need validation or not */
    public $needValidation = false;

    /** what you get when you read() */
    public $userData;

    /** our userid */
    public $userid;

    /**
     * Constructor
     *
     * @param int|null $userid
     * @param Config|null $config
     */
    public function __construct($userid = null, Config $config = null)
    {
        $this->pdo = Db::getConnection();
        if (!is_null($userid)) {
            $this->setId($userid);
        }
        if (!is_null($config)) {
            $this->Config = $config;
        }
    }

    /**
     * Assign an id and populate userData
     *
     * @param int $userid
     */
    public function setId($userid)
    {
        if (Tools::checkId($userid) === false) {
            throw new Exception('Bad userid');
        }
        $this->userid = $userid;
        $this->populate();
    }

    /**
     * Populate userData with read()
     *
     */
    public function populate()
    {
        $this->userData = $this->read($this->userid);
    }

    /**
     * Create a new user
     *
     * @param string $email
     * @param string $password
     * @param int $team
     * @param string $firstname
     * @param string $lastname
     * @return bool
     */
    public function create($email, $password, $team, $firstname, $lastname)
    {
        // check for duplicate of email
        if ($this->isDuplicateEmail($email)) {
            throw new Exception(_('Someone is already using that email address!'));
        }

        if (!$this->checkPasswordLength($password)) {
            $error = sprintf(_('Password must contain at least %s characters.'), self::MIN_PASSWORD_LENGTH);
            throw new Exception($error);
        }

        // Put firstname lowercase and first letter uppercase
        $firstname = Tools::purifyFirstname($firstname);
        // lastname is uppercase
        $lastname = Tools::purifyLastname($lastname);

        // Create salt
        $salt = hash("sha512", uniqid(rand(), true));
        // Create hash
        $passwordHash = hash("sha512", $salt . $_POST['password']);

        // Registration date is stored in epoch
        $registerDate = time();

        // get the group for the new user
        $group = $this->getGroup($team);

        // will new user be validated?
        $validated = $this->getValidated($group);

        $sql = "INSERT INTO users (
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
            :lang);";
        $req = $this->pdo->prepare($sql);

        $req->bindParam(':email', $email);
        $req->bindParam(':team', $team);
        $req->bindParam(':salt', $salt);
        $req->bindParam(':password', $passwordHash);
        $req->bindParam(':firstname', $firstname);
        $req->bindParam(':lastname', $lastname);
        $req->bindParam(':register_date', $registerDate);
        $req->bindParam(':validated', $validated);
        $req->bindParam(':usergroup', $group);
        $req->bindValue(':lang', $this->Config->configArr['lang']);

        if (!$req->execute()) {
            throw new Exception('Error inserting user in SQL!');
        }

        if ($validated == '0') {
            $this->alertAdmin($team);
            $this->needValidation = true;
        }

        return true;
    }

    /**
     * Get what will be the value of the validated column in users table
     *
     * @param int $group
     * @return int
     */
    private function getValidated($group)
    {
        if ($this->Config->configArr['admin_validate'] === "1" && $group === 4) { // validation is required for normal user
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
    private function getGroup($team)
    {
        if ($this->isFirstUser()) {
            return 1;
        } elseif ($this->isFirstUserInTeam($team)) {
            return 2;
        }
        return 4;
    }

    /**
     * Send an email to the admin of a team
     *
     * @param int $team
     * @throws Exception
     */
    private function alertAdmin($team)
    {
        $Email = new Email($this->Config);

        // Create the message
        $footer = "\n\n~~~\nSent from eLabFTW https://www.elabftw.net\n";
        $message = Swift_Message::newInstance()
        // Give the message a subject
        ->setSubject(_('[eLabFTW] New user registered'))
        // Set the From address with an associative array
        ->setFrom(array($this->Config->configArr['mail_from'] => 'eLabFTW'))
        // Set the To
        ->setTo($this->getAdminEmail($team))
        // Give it a body
        ->setBody(_('Hi. A new user registered on elabftw. Head to the admin panel to validate the account.') . $footer);
        // generate Swift_Mailer instance
        $mailer = $Email->getMailer();
        // SEND EMAIL
        try {
            $mailer->send($message);
        } catch (Exception $e) {
            $Logs = new Logs();
            $Logs->create('Error', 'smtp', $e->getMessage());
            throw new Exception(_('Could not send email to inform admin. Error was logged. Contact an admin directly to validate your account.'));
        }
    }

    /**
     * Check we have not a duplicate email in DB
     *
     * @param string $email
     * @return bool true if there is a duplicate
     */
    public function isDuplicateEmail($email)
    {
        $sql = "SELECT email FROM users WHERE email = :email";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':email', $email);
        $req->execute();

        return (bool) $req->rowCount();
    }

    /**
     * Do we have users in the DB ?
     *
     * @return bool
     */
    private function isFirstUser()
    {
        $sql = "SELECT COUNT(*) AS usernb FROM users";
        $req = $this->pdo->prepare($sql);
        $req->execute();
        $test = $req->fetch();

        return $test['usernb'] === '0';
    }

    /**
     * Are we the first user to register in a team ?
     *
     * @param int $team
     * @return bool
     */
    private function isFirstUserInTeam($team)
    {
        $sql = "SELECT COUNT(*) AS usernb FROM users WHERE team = :team";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $team);
        $req->execute();
        $test = $req->fetch();

        return $test['usernb'] === '0';
    }

    /**
     * Fetch the email(s) of the admin(s) for a team
     *
     * @param int $team
     * @return array
     */
    private function getAdminEmail($team)
    {
        // array for storing email adresses of admin(s)
        $arr = array();

        $sql = "SELECT email FROM users WHERE (`usergroup` = 1 OR `usergroup` = 2) AND `team` = :team";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':team', $team);
        $req->execute();

        while ($email = $req->fetchColumn()) {
            $arr[] = $email;
        }

        // if we have only one admin, we need to have an associative array
        if (count($arr) === 1) {
            return array($arr[0] => 'Admin eLabFTW');
        }

        return $arr;
    }

    /**
     * Get info about a user
     *
     * @param int $userid
     * @return array
     */
    public function read($userid)
    {
        $sql = "SELECT users.*, CONCAT(users.firstname, ' ', users.lastname) AS fullname, groups.can_lock FROM users
            LEFT JOIN groups ON groups.group_id = users.usergroup
            WHERE users.userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':userid', $userid);
        $req->execute();

        return $req->fetch();
    }

    /**
     * Select by email
     *
     * @param string $email
     * @return array
     */
    public function readFromEmail($email)
    {
        $sql = "SELECT userid, CONCAT(firstname, ' ', lastname) AS fullname FROM users WHERE email = :email";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':email', $email);
        $req->execute();

        return $req->fetch();
    }

    /**
     * Get a user from his API key
     *
     * @param string $apiKey
     */
    public function readFromApiKey($apiKey)
    {
        $sql = "SELECT userid FROM users WHERE api_key = :key";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':key', $apiKey);
        $req->execute();

        $userid = $req->fetchColumn();

        if (empty($userid)) {
            throw new Exception('Invalid API key.');
        }

        $this->userData = $this->read($userid);
    }

    /**
     * Read all users from the team
     *
     * @param int $team
     * @param int $validated
     * @return array
     */
    public function readAllFromTeam($team, $validated = 1)
    {
        $sql = "SELECT *, CONCAT (firstname, ' ', lastname) AS fullname
            FROM users WHERE validated = :validated AND team = :team";
        $req = $this->pdo->prepare($sql);
        $req->bindValue(':validated', $validated);
        $req->bindValue(':team', $team);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Get all users
     *
     * @return array
     */
    public function readAll()
    {
        $sql = "SELECT users.*, teams.team_name AS teamname
            FROM users
            LEFT JOIN teams ON (users.team = teams.team_id)
            ORDER BY users.team ASC, users.usergroup ASC, users.lastname ASC";
        $req = $this->pdo->prepare($sql);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Get email for every single user
     *
     * @return array
     */
    public function getAllEmails()
    {
        $sql = "SELECT email FROM users WHERE validated = 1";
        $req = $this->pdo->prepare($sql);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Update user
     *
     * @param array $params POST
     * @throws Exception
     * @return bool
     */
    public function update($params)
    {
        $userid = Tools::checkId($params['userid']);

        if ($userid === false) {
            throw new Exception(_('The id parameter is not valid!'));
        }

        // Put everything lowercase and first letter uppercase
        $firstname = Tools::purifyFirstname($params['firstname']);
        // Lastname in uppercase
        $lastname = Tools::purifyLastname($params['lastname']);

        $email = filter_var($params['email'], FILTER_SANITIZE_EMAIL);

        if ($params['validated'] == 1) {
            $validated = 1;
        } else {
            $validated = 0;
        }
        $usergroup = Tools::checkId($params['usergroup']);
        if ($usergroup === false) {
            throw new Exception(_('The id parameter is not valid!'));
        }

        // a non sysadmin cannot put someone sysadmin
        if ($usergroup == 1 && $_SESSION['is_sysadmin'] != 1) {
            throw new Exception(_('Only a sysadmin can put someone sysadmin.'));
        }

        if (strlen($params['password']) > 1) {
            $this->updatePassword($params['password'], $userid);
        }

        $sql = "UPDATE users SET
            firstname = :firstname,
            lastname = :lastname,
            email = :email,
            usergroup = :usergroup,
            validated = :validated
            WHERE userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':firstname', $firstname);
        $req->bindParam(':lastname', $lastname);
        $req->bindParam(':email', $email);
        $req->bindParam(':validated', $validated);
        $req->bindParam(':usergroup', $usergroup);
        $req->bindParam(':userid', $userid);

        return $req->execute();
    }

    /**
     * Update preferences from user control panel
     *
     * @param array $params
     * @return bool
     */
    public function updatePreferences($params)
    {
        // DISPLAY
        $new_display = 'default';
        if ($params['display'] === 'compact') {
            $new_display = 'compact';
        }

        // LIMIT
        $filter_options = array(
            'options' => array(
                'default' => 15,
                'min_range' => 1,
                'max_range' => 500
            ));
        $new_limit = filter_var($params['limit'], FILTER_VALIDATE_INT, $filter_options);

        // KEYBOARD SHORTCUTS
        // only take first letter
        $new_sc_create = substr($params['sc_create'], 0, 1);
        if (!ctype_alpha($new_sc_create)) {
            $new_sc_create = 'c';
        }
        $new_sc_edit = substr($params['sc_edit'], 0, 1);
        if (!ctype_alpha($new_sc_edit)) {
            $new_sc_edit = 'e';
        }
        $new_sc_submit = substr($params['sc_submit'], 0, 1);
        if (!ctype_alpha($new_sc_submit)) {
            $new_sc_submit = 's';
        }
        $new_sc_todo = substr($params['sc_todo'], 0, 1);
        if (!ctype_alpha($new_sc_todo)) {
            $new_sc_todo = 't';
        }

        // SHOW TEAM
        if (isset($params['show_team']) && $params['show_team'] === 'on') {
            $new_show_team = 1;
        } else {
            $new_show_team = 0;
        }

        // CLOSE WARNING
        if (isset($params['close_warning']) && $params['close_warning'] === 'on') {
            $new_close_warning = 1;
        } else {
            $new_close_warning = 0;
        }
        // CHEM EDITOR
        if (isset($params['chem_editor']) && $params['chem_editor'] === 'on') {
            $new_chem_editor = 1;
        } else {
            $new_chem_editor = 0;
        }

        // LANG
        $lang_array = array('en_GB', 'ca_ES', 'de_DE', 'es_ES', 'fr_FR', 'it_IT', 'pl_PL', 'pt_BR', 'pt_PT', 'ru_RU', 'sl_SI', 'zh_CN');
        if (isset($params['lang']) && in_array($params['lang'], $lang_array)) {
            $new_lang = $params['lang'];
        } else {
            $new_lang = 'en_GB';
        }

        // DEFAULT VIS
        $new_default_vis = null;
        $Experiments = new Experiments($this);
        if (isset($params['default_vis']) && $Experiments->checkVisibility($params['default_vis'])) {
            $new_default_vis = $params['default_vis'];
        }

        $sql = "UPDATE users SET
            display = :new_display,
            limit_nb = :new_limit,
            sc_create = :new_sc_create,
            sc_edit = :new_sc_edit,
            sc_submit = :new_sc_submit,
            sc_todo = :new_sc_todo,
            show_team = :new_show_team,
            close_warning = :new_close_warning,
            chem_editor = :new_chem_editor,
            lang = :new_lang,
            default_vis = :new_default_vis
            WHERE userid = :userid;";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':new_display', $new_display);
        $req->bindParam(':new_limit', $new_limit);
        $req->bindParam(':new_sc_create', $new_sc_create);
        $req->bindParam(':new_sc_edit', $new_sc_edit);
        $req->bindParam(':new_sc_submit', $new_sc_submit);
        $req->bindParam(':new_sc_todo', $new_sc_todo);
        $req->bindParam(':new_show_team', $new_show_team);
        $req->bindParam(':new_close_warning', $new_close_warning);
        $req->bindParam(':new_chem_editor', $new_chem_editor);
        $req->bindParam(':new_lang', $new_lang);
        $req->bindParam(':new_default_vis', $new_default_vis);
        $req->bindParam(':userid', $this->userid);

        return $req->execute();
    }

    /**
     * Update things from UCP
     *
     * @param array $params
     * @return bool
     */
    public function updateAccount($params)
    {
        $Auth = new Auth();
        // check that we got the good password
        if (!$Auth->checkCredentials($this->userData['email'], $params['currpass'])) {
            throw new Exception(_("Please input your current password!"));
        }
        // PASSWORD CHANGE
        if (strlen($params['newpass']) >= Auth::MIN_PASSWORD_LENGTH) {
            if ($params['newpass'] != $params['cnewpass']) {
                throw new Exception(_('The passwords do not match!'));
            }

            $this->updatePassword($params['newpass']);
        }

        $params['firstname'] = Tools::purifyFirstname($params['firstname']);
        $params['lastname'] = Tools::purifyLastname($params['lastname']);

        $params['email'] = filter_var($params['email'], FILTER_SANITIZE_EMAIL);

        if ($this->isDuplicateEmail($params['email']) && ($params['email'] != $this->userData['email'])) {
            throw new Exception(_('Someone is already using that email address!'));
        }

        // Check phone
        $params['phone'] = filter_var($params['phone'], FILTER_SANITIZE_STRING);
        // Check cellphone
        $params['cellphone'] = filter_var($params['cellphone'], FILTER_SANITIZE_STRING);
        // Check skype
        $params['skype'] = filter_var($params['skype'], FILTER_SANITIZE_STRING);

        // Check website
        if (!filter_var($params['website'], FILTER_VALIDATE_URL)) {
            throw new Exception(_('A mandatory field is missing!'));
        }

        $sql = "UPDATE users SET
            email = :email,
            firstname = :firstname,
            lastname = :lastname,
            phone = :phone,
            cellphone = :cellphone,
            skype = :skype,
            website = :website
            WHERE userid = :userid";
        $req = $this->pdo->prepare($sql);

        $req->bindParam(':email', $params['email']);
        $req->bindParam(':firstname', $params['firstname']);
        $req->bindParam(':lastname', $params['lastname']);
        $req->bindParam(':phone', $params['phone']);
        $req->bindParam(':cellphone', $params['cellphone']);
        $req->bindParam(':skype', $params['skype']);
        $req->bindParam(':website', $params['website']);
        $req->bindParam(':userid', $this->userid);

        return $req->execute();
    }

    /**
     * Update the password for a user, or for ourself if none provided
     *
     * @param string $password The new password
     * @param int|null $userid The user we want to update
     * @throws Exception if invalid character length
     * @return bool True if password is updated
     */
    public function updatePassword($password, $userid = null)
    {

        if (is_null($userid)) {
            $userid = $_SESSION['userid'];
        }

        if (!$this->checkPasswordLength($password)) {
            $error = sprintf(_('Password must contain at least %s characters.'), self::MIN_PASSWORD_LENGTH);
            throw new Exception($error);
        }

        $salt = hash("sha512", uniqid(rand(), true));
        $passwordHash = hash("sha512", $salt . $password);

        $sql = "UPDATE users SET salt = :salt, password = :password WHERE userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':salt', $salt);
        $req->bindParam(':password', $passwordHash);
        $req->bindParam(':userid', $userid);

        // remove token for this user
        if (!$this->invalidateToken($userid)) {
            throw new Exception('Cannot invalidate token');
        }

        return $req->execute();
    }

    /**
     * Invalidate token for a user
     *
     * @param int $userid
     * @return bool
     */
    private function invalidateToken($userid)
    {
        $sql = "UPDATE users SET token = null WHERE userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':userid', $userid);

        return $req->execute();
    }

    /**
     * Validate a user
     *
     * @param int $userid
     * @return string
     */
    public function validate($userid)
    {
        $userid = Tools::checkId($userid);
        if ($userid === false) {
            throw new Exception('The id parameter is not valid!');
        }

        $sql = "UPDATE users SET validated = 1 WHERE userid = :userid";
        $req = $this->pdo->prepare($sql);

        // we read to get email of user
        $userArr = $this->read($userid);

        $req->bindParam(':userid', $userid, PDO::PARAM_INT);

        // validate the user
        if ($req->execute()) {
            $msg = _('Validated user with ID :') . ' ' . $userid;
        } else {
            $msg = Tools::error();
        }

        $Email = new Email($this->Config);

        // now let's get the URL so we can have a nice link in the email
        $url = 'https://' . $_SERVER['SERVER_NAME'] . Tools::getServerPort() . $_SERVER['PHP_SELF'];
        $url = str_replace('app/controllers/UsersController.php', 'login.php', $url);
        // we send an email to each validated new user
        $footer = "\n\n~~~\nSent from eLabFTW https://www.elabftw.net\n";
        // Create the message
        $message = Swift_Message::newInstance()
        // Give the message a subject
        // no i18n here
        ->setSubject('[eLabFTW] Account validated')
        // Set the From address with an associative array
        ->setFrom(array($this->Config->configArr['mail_from'] => 'eLabFTW'))
        // Set the To addresses with an associative array
        ->setTo(array($userArr['email'] => 'eLabFTW'))
        // Give it a body
        ->setBody('Hello. Your account on eLabFTW was validated by an admin. Follow this link to login : ' . $url . $footer);
        // generate Swift_Mailer instance
        $mailer = $Email->getMailer();
        // now we try to send the email
        try {
            $mailer->send($message);
        } catch (Exception $e) {
            throw new Exception(_('There was a problem sending the email! Error was logged.'));
        }

        return $msg;
    }

    /**
     * Destroy user. Will completely remove everything from the user.
     *
     * @param string $email The email of the user we want to delete
     * @param string $password The confirmation password
     * @return bool
     */
    public function destroy($email, $password)
    {
        // check that we got the good password
        $me = $this->read($_SESSION['userid']);
        if (!$this->checkCredentials($me['email'], $password)) {
            throw new Exception(_("Wrong password!"));
        }
        // check the user is in our team and also get the userid
        $useridArr = $this->emailInTeam($email, $_SESSION['team_id']);
        $userid = $useridArr['userid'];

        if (!$userid) {
            throw new Exception(_('No user with this email or user not in your team'));
        }

        $result = array();

        $sql = "DELETE FROM users WHERE userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $result[] = $req->execute();

        $sql = "DELETE FROM experiments_tags WHERE userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $result[] = $req->execute();

        $sql = "DELETE FROM experiments WHERE userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $result[] = $req->execute();

        // get all filenames
        $sql = "SELECT long_name FROM uploads WHERE userid = :userid AND type = :type";
        $req = $this->pdo->prepare($sql);
        $req->execute(array(
            'userid' => $userid,
            'type' => 'experiments'
        ));
        while ($uploads = $req->fetch()) {
            // Delete file
            $filepath = ELAB_ROOT . 'uploads/' . $uploads['long_name'];
            $result[] = unlink($filepath);
        }

        $sql = "DELETE FROM uploads WHERE userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        $result[] = $req->execute();

        return !in_array(0, $result);
    }

    /**
     * Check if a user is in our team
     *
     * @param string $email
     * @param int $team
     * @return int|bool
     */
    private function emailInTeam($email, $team)
    {
        $sql = "SELECT userid FROM users WHERE email LIKE :email AND team = :team";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':email', $email);
        $req->bindParam(':team', $team);
        $req->execute();

        return $req->fetch();
    }

    /**
     * Make a user sysadmin
     *
     * @param string $email Email of user to promote
     * @return bool
     */
    public function promoteSysadmin($email)
    {
        // only sysadmin can do that
        if (!$_SESSION['is_sysadmin']) {
            throw new Exception(Tools::error(true));
        }

        // check we have a valid email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email malformed');
        }

        $sql = "UPDATE users SET usergroup = 1 WHERE email = :email";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':email', $email);

        return $req->execute();
    }

    /**
     * Generate an API key and store it
     *
     * @return bool
     */
    public function generateApiKey()
    {
        $apiKey = bin2hex(openssl_random_pseudo_bytes(42));

        $sql = "UPDATE users SET api_key = :api_key WHERE userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':api_key', $apiKey);
        $req->bindParam(':userid', $this->userid);

        return $req->execute();
    }
}
