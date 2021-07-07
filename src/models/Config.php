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

use function array_map;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Sql;
use Elabftw\Elabftw\Update;
use Elabftw\Exceptions\DatabaseErrorException;
use PDO;

/**
 * The general config table
 */
final class Config
{
    // the array with all config
    public array $configArr = array();

    protected Db $Db;

    // store the single instance of the class
    private static ?Config $instance = null;

    /**
     * Construct of a singleton is private
     *
     * Get Db and load the configArr
     */
    private function __construct()
    {
        $this->Db = Db::getConnection();
        $this->configArr = $this->read();
        // this should only run once: just after a fresh install
        if (empty($this->configArr)) {
            $this->populate();
            $this->configArr = $this->read();
        }
    }

    /**
     * Disallow cloning the class
     * @norector \Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector
     */
    private function __clone()
    {
    }

    /**
     * Disallow wakeup also
     * @norector \Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector
     */
    public function __wakeup()
    {
    }

    /**
     * Return the instance of the class
     *
     * @throws DatabaseErrorException If config can not be loaded
     * @return Config The instance of the class
     */
    public static function getConfig(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Read the configuration values
     */
    public function read(): array
    {
        $sql = 'SELECT * FROM config';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        $config = $req->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP);
        if ($config === false) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
        return array_map(function ($v) {
            return $v[0];
        }, $config);
    }

    /**
     * Used in sysconfig.php to update config values
     * NOTE: it is unlikely that someone with sysadmin level tries and edit requests to input incorrect values
     * so there is no real need for ensuring the values make sense, client side validation is enough this time
     *
     * @param array<string, mixed> $post (conf_name => conf_value)
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public function update(array $post): void
    {
        $passwords = array('stamppass', 'smtp_password', 'ldap_password');

        foreach ($passwords as $password) {
            if (isset($post[$password]) && !empty($post[$password])) {
                $post[$password] = Crypto::encrypt($post[$password], Key::loadFromAsciiSafeString(\SECRET_KEY));
            // if it's not changed, it is sent anyway, but we don't want it in the final array as it will blank the existing one
            } elseif (isset($post[$password])) {
                unset($post[$password]);
            }
        }

        // loop the array and update config
        foreach ($post as $name => $value) {
            $sql = 'UPDATE config SET conf_value = :value WHERE conf_name = :name';
            $req = $this->Db->prepare($sql);
            $req->bindParam(':value', $value);
            $req->bindParam(':name', $name);
            $this->Db->execute($req);
            $this->configArr[$name] = $value;
        }
    }

    /**
     * Reset the timestamp password
     */
    public function destroyStamppass(): bool
    {
        $sql = "UPDATE config SET conf_value = NULL WHERE conf_name = 'stamppass'";
        $req = $this->Db->prepare($sql);
        return $this->Db->execute($req);
    }

    /**
     * Restore default values
     */
    public function restoreDefaults(): bool
    {
        $sql = 'DELETE FROM config';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        return $this->populate();
    }

    /**
     * Insert the default values in the sql config table
     * Only run once of first ever page load
     */
    private function populate(): bool
    {
        $Update = new Update($this, new Sql());
        $schema = $Update->getRequiredSchema();

        $sql = "INSERT INTO `config` (`conf_name`, `conf_value`) VALUES
            ('admin_validate', '1'),
            ('autologout_time', '0'),
            ('ban_time', '60'),
            ('debug', '0'),
            ('lang', 'en_GB'),
            ('login_tries', '3'),
            ('mail_from', 'notconfigured@example.com'),
            ('mail_method', 'smtp'),
            ('proxy', ''),
            ('sendmail_path', '/usr/sbin/sendmail'),
            ('smtp_address', 'mail.smtp2go.com'),
            ('smtp_encryption', 'ssl'),
            ('smtp_password', ''),
            ('smtp_port', '587'),
            ('smtp_username', ''),
            ('stamplogin', ''),
            ('stamppass', ''),
            ('stampshare', '1'),
            ('stampprovider', 'http://zeitstempel.dfn.de/'),
            ('stampcert', 'src/dfn-cert/pki.dfn.pem'),
            ('stamphash', 'sha256'),
            ('saml_toggle', '0'),
            ('saml_debug', '0'),
            ('saml_strict', '1'),
            ('saml_baseurl', NULL),
            ('saml_entityid', NULL),
            ('saml_acs_binding', NULL),
            ('saml_slo_binding', NULL),
            ('saml_nameidformat', NULL),
            ('saml_x509', NULL),
            ('saml_privatekey', NULL),
            ('saml_team', NULL),
            ('saml_team_create', '1'),
            ('saml_team_default', NULL),
            ('saml_user_default', '1'),
            ('saml_email', NULL),
            ('saml_firstname', NULL),
            ('saml_lastname', NULL),
            ('local_login', '1'),
            ('local_register', '1'),
            ('admins_create_users', '1'),
            ('anon_users', '0'),
            ('url', NULL),
            ('schema', :schema),
            ('open_science', '0'),
            ('open_team', NULL),
            ('privacy_policy', NULL),
            ('announcement', NULL),
            ('login_announcement', NULL),
            ('saml_nameidencrypted', 0),
            ('saml_authnrequestssigned', 0),
            ('saml_logoutrequestsigned', 0),
            ('saml_logoutresponsesigned', 0),
            ('saml_signmetadata', 0),
            ('saml_wantmessagessigned', 0),
            ('saml_wantassertionsencrypted', 0),
            ('saml_wantassertionssigned', 0),
            ('saml_wantnameid', 1),
            ('saml_wantnameidencrypted', 0),
            ('saml_wantxmlvalidation', 1),
            ('saml_relaxdestinationvalidation', 0),
            ('saml_lowercaseurlencoding', 0),
            ('email_domain', NULL),
            ('saml_sync_teams', 0),
            ('deletable_xp', 1),
            ('max_revisions', 10),
            ('min_delta_revisions', 100),
            ('min_days_revisions', 23),
            ('extauth_remote_user', ''),
            ('extauth_firstname', ''),
            ('extauth_lastname', ''),
            ('extauth_email', ''),
            ('extauth_teams', ''),
            ('logout_url', ''),
            ('ldap_toggle', '0'),
            ('ldap_host', ''),
            ('ldap_port', '389'),
            ('ldap_base_dn', ''),
            ('ldap_username', NULL),
            ('ldap_password', NULL),
            ('ldap_uid_cn', 'cn'),
            ('ldap_email', 'mail'),
            ('ldap_lastname', 'cn'),
            ('ldap_firstname', 'givenname'),
            ('ldap_team', 'on'),
            ('ldap_use_tls', '0')";

        $req = $this->Db->prepare($sql);
        $req->bindParam(':schema', $schema);

        return $this->Db->execute($req);
    }
}
