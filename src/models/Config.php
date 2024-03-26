<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use function array_map;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\AuditEvent\ConfigModified;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\TwigFilters;
use Elabftw\Elabftw\Update;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use PDO;
use function urlencode;

/**
 * The general config table
 */
final class Config implements RestInterface
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
        $this->configArr = $this->readAll();
        // this should only run once: just after a fresh install
        if (empty($this->configArr)) {
            $this->create();
            $this->configArr = $this->readAll();
        }
    }

    /**
     * Insert the default values in the sql config table
     * Only run once of first ever page load
     */
    public function create(): bool
    {
        $schema = Update::getRequiredSchema();

        $sql = "INSERT INTO `config` (`conf_name`, `conf_value`) VALUES
            ('admin_validate', '1'),
            ('autologout_time', '0'),
            ('cookie_validity_time', '43200'),
            ('remember_me_checked', '1'),
            ('remember_me_allowed', '1'),
            ('debug', '0'),
            ('lang', 'en_GB'),
            ('login_tries', '3'),
            ('mail_from', 'notconfigured@example.com'),
            ('proxy', ''),
            ('user_msg_need_local_account_created', ''),
            ('smtp_address', 'mail.smtp2go.com'),
            ('smtp_encryption', 'ssl'),
            ('smtp_password', ''),
            ('smtp_port', '587'),
            ('smtp_username', ''),
            ('smtp_verify_cert', '1'),
            ('ts_authority', 'dfn'),
            ('ts_balance', '0'),
            ('ts_login', NULL),
            ('ts_password', NULL),
            ('ts_url', 'NULL'),
            ('ts_cert', NULL),
            ('ts_hash', 'sha256'),
            ('ts_limit', '0'),
            ('keeex_enabled', '0'),
            ('keeex_host', 'keeex'),
            ('keeex_port', '8080'),
            ('saml_toggle', '0'),
            ('saml_debug', '0'),
            ('saml_strict', '1'),
            ('saml_baseurl', NULL),
            ('saml_entityid', NULL),
            ('saml_acs_binding', NULL),
            ('saml_slo_binding', NULL),
            ('saml_nameidformat', 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress'),
            ('saml_x509', NULL),
            ('saml_x509_new', NULL),
            ('saml_privatekey', NULL),
            ('saml_team_create', '1'),
            ('saml_team_default', '-1'),
            ('saml_user_default', '1'),
            ('saml_allowrepeatattributename', '0'),
            ('local_login', '1'),
            ('local_register', '1'),
            ('admins_create_users', '1'),
            ('anon_users', '0'),
            ('schema', :schema),
            ('open_science', '0'),
            ('open_team', NULL),
            ('privacy_policy', NULL),
            ('privacy_policy_name', 'Privacy policy'),
            ('terms_of_service', NULL),
            ('terms_of_service_name', 'Terms of service'),
            ('a11y_statement', NULL),
            ('a11y_statement_name', 'Accessibility statement'),
            ('legal_notice', NULL),
            ('legal_notice_name', 'Legal notice'),
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
            ('saml_fallback_orgid', '0'),
            ('email_domain', NULL),
            ('saml_sync_teams', 0),
            ('saml_sync_email_idp', '0'),
            ('support_url', 'https://github.com/elabftw/elabftw/issues'),
            ('chat_url', 'https://gitter.im/elabftw/elabftw'),
            ('allow_useronly', '1'),
            ('admins_import_users', '0'),
            ('admins_archive_users', '1'),
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
            ('ldap_search_attr', 'mail'),
            ('ldap_host', ''),
            ('ldap_port', '389'),
            ('ldap_base_dn', ''),
            ('ldap_username', NULL),
            ('ldap_password', NULL),
            ('ldap_email', 'mail'),
            ('ldap_lastname', 'cn'),
            ('ldap_firstname', 'givenname'),
            ('ldap_team', 'on'),
            ('ldap_use_tls', '0'),
            ('uploads_storage', '1'),
            ('s3_bucket_name', ''),
            ('s3_path_prefix', ''),
            ('s3_region', ''),
            ('s3_endpoint', ''),
            ('s3_verify_cert', '1'),
            ('blox_anon', '0'),
            ('blox_enabled', '1'),
            ('enforce_mfa', '0'),
            ('emit_audit_logs', '0'),
            ('admins_create_users_remote_dir', '0'),
            ('min_password_length', '12'),
            ('password_complexity_requirement', '0'),
            ('max_password_age_days', '3650'),
            ('remote_dir_service', 'eairef'),
            ('remote_dir_config', NULL)";

        $req = $this->Db->prepare($sql);
        $req->bindParam(':schema', $schema);

        return $this->Db->execute($req);
    }

    /**
     * Return the instance of the class
     */
    public static function getConfig(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get a core config value from php-fpm env
     */
    public static function fromEnv(string $confName): string
    {
        return (string) getenv($confName);
    }

    public function readOne(): array
    {
        return $this->readAll();
    }

    public function decrementTsBalance(): array
    {
        $tsBalance = (int) $this->configArr['ts_balance'];
        if ($tsBalance > 0) {
            return $this->patch(Action::Update, array('ts_balance' => (string) ($tsBalance - 1)));
        }
        return $this->readOne();
    }

    public function readAll(): array
    {
        $sql = 'SELECT * FROM config';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        $config = $req->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP);

        // special case for remote_dir_config where we decrypt it in output so it can be used by external scripts
        if (!empty($config['remote_dir_config'])) {
            $config['remote_dir_config'][0] = TwigFilters::decrypt($config['remote_dir_config'][0]);
        }

        return array_map(function ($v): mixed {
            return $v[0];
        }, $config);
    }

    /**
     * Used in sysconfig.php to update config values
     * NOTE: it is unlikely that someone with sysadmin level tries and edit requests to input incorrect values
     * so there is no real need for ensuring the values make sense, client side validation is enough this time
     *
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public function patch(Action $action, array $params): array
    {
        $passwords = array('smtp_password', 'ldap_password', 'ts_password', 'remote_dir_config');

        foreach ($passwords as $password) {
            if (isset($params[$password]) && !empty($params[$password])) {
                $params[$password] = Crypto::encrypt($params[$password], Key::loadFromAsciiSafeString(self::fromEnv('SECRET_KEY')));
                // if it's not changed, it is sent anyway, but we don't want it in the final array as it will blank the existing one
            } elseif (isset($params[$password])) {
                unset($params[$password]);
            }
        }

        $sql = 'UPDATE config SET conf_value = :value WHERE conf_name = :name';
        $req = $this->Db->prepare($sql);

        // loop the array and update config
        foreach ($params as $name => $value) {
            if ($this->configArr[$name] !== $value) {
                $req->bindParam(':value', $value);
                $req->bindParam(':name', $name);
                $this->Db->execute($req);
                AuditLogs::create(new ConfigModified($name, (string) $this->configArr[$name], (string) $value));
                $this->configArr[$name] = (string) $value;
            }
        }

        return $this->readAll();
    }

    public function getPage(): string
    {
        return 'api/v2/config/';
    }

    public function getDsn(): string
    {
        $username = '';
        $password = '';
        if ($this->configArr['smtp_password']) {
            $username = $this->configArr['smtp_username'];
            $password = Crypto::decrypt(
                $this->configArr['smtp_password'],
                Key::loadFromAsciiSafeString(self::fromEnv('SECRET_KEY'))
            );
        }

        return sprintf(
            'smtp://%s:%s@%s:%d?verify_peer=%s',
            $username,
            urlencode($password),
            $this->configArr['smtp_address'],
            $this->configArr['smtp_port'],
            $this->configArr['smtp_verify_cert'],
        );
    }

    public function postAction(Action $action, array $reqBody): int
    {
        throw new ImproperActionException('No POST action for Config endpoint.');
    }

    /**
     * Restore default values
     */
    public function destroy(): bool
    {
        $sql = 'DELETE FROM config';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        $createResult = $this->create();
        $this->configArr = $this->readAll();
        return $createResult;
    }
}
