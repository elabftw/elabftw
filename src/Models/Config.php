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

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\AuditEvent\ConfigModified;
use Elabftw\Elabftw\Env;
use Elabftw\Elabftw\TwigFilters;
use Elabftw\Elabftw\Update;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Exceptions\UnprocessableContentException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Services\Filter;
use PDO;
use Override;

use function array_map;
use function urlencode;
use function apcu_fetch;
use function apcu_store;
use function apcu_exists;
use function apcu_delete;

/**
 * The general config table
 */
final class Config extends AbstractRest
{
    private const string CACHE_KEY = 'config_table';

    private const int CACHE_TTL_SECONDS = 9001;

    // the array with all config
    public array $configArr = array();

    // store the single instance of the class
    private static ?Config $instance = null;

    /**
     * Construct of a singleton is private
     *
     * Get Db and load the configArr
     */
    private function __construct()
    {
        parent::__construct();
        $this->configArr = $this->readAll();
        // this should only run once: just after a fresh install
        if (empty($this->configArr)) {
            $this->create();
            $this->configArr = $this->readAll();
        }
    }

    public function bustCache(): void
    {
        apcu_delete(self::CACHE_KEY);
        $this->configArr = $this->readAll();
    }

    /**
     * Insert the default values in the sql config table
     * Only run once of first ever page load
     */
    public function create(): bool
    {
        $sql = "INSERT INTO `config` (`conf_name`, `conf_value`) VALUES
            ('admin_validate', '1'),
            ('autologout_time', '0'),
            ('cookie_validity_time', '43200'),
            ('remember_me_checked', '1'),
            ('remember_me_allowed', '1'),
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
            ('ts_url', NULL),
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
            ('local_login_hidden_only_sysadmin', '0'),
            ('local_login_only_sysadmin', '0'),
            ('local_auth_enabled', '1'),
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
            ('saml_nameidencrypted', '0'),
            ('saml_authnrequestssigned', '0'),
            ('saml_logoutrequestsigned', '0'),
            ('saml_logoutresponsesigned', '0'),
            ('saml_signmetadata', '0'),
            ('saml_wantmessagessigned', '0'),
            ('saml_wantassertionsencrypted', '0'),
            ('saml_wantassertionssigned', '0'),
            ('saml_wantnameid', '1'),
            ('saml_wantnameidencrypted', '0'),
            ('saml_wantxmlvalidation', '1'),
            ('saml_relaxdestinationvalidation', '0'),
            ('saml_lowercaseurlencoding', '0'),
            ('saml_fallback_orgid', '0'),
            ('email_domain', NULL),
            ('email_send_grouped', '1'),
            ('saml_sync_teams', '0'),
            ('saml_sync_email_idp', '0'),
            ('support_url', 'https://github.com/elabftw/elabftw/issues'),
            ('chat_url', 'https://gitter.im/elabftw/elabftw'),
            ('allow_permission_full', '1'),
            ('allow_permission_organization', '1'),
            ('allow_permission_team', '1'),
            ('allow_permission_user', '1'),
            ('allow_permission_useronly', '1'),
            ('admins_import_users', '0'),
            ('admins_archive_users', '1'),
            ('max_revisions', '10'),
            ('min_delta_revisions', '100'),
            ('min_days_revisions', '23'),
            ('extauth_remote_user', ''),
            ('extauth_firstname', ''),
            ('extauth_lastname', ''),
            ('extauth_email', ''),
            ('extauth_teams', ''),
            ('logout_url', ''),
            ('ldap_toggle', '0'),
            ('ldap_scheme', 'ldap'),
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
            ('s3_use_path_style_endpoint', '0'),
            ('blox_anon', '0'),
            ('blox_enabled', '1'),
            ('enforce_mfa', '0'),
            ('emit_audit_logs', '0'),
            ('admins_create_users_remote_dir', '0'),
            ('min_password_length', '12'),
            ('password_complexity_requirement', '0'),
            ('max_password_age_days', '3650'),
            ('remote_dir_service', 'eairef'),
            ('remote_dir_config', NULL),
            ('onboarding_email_active', '0'),
            ('onboarding_email_subject', NULL),
            ('onboarding_email_body', NULL),
            ('onboarding_email_different_for_admins', '0'),
            ('onboarding_email_admins_subject', NULL),
            ('onboarding_email_admins_body', NULL),
            ('allow_users_change_identity', '0'),
            ('compounds_require_edit_rights', '0'),
            ('inventory_require_edit_rights', '0')";

        $req = $this->Db->prepare($sql);
        $req->bindValue(':schema', Update::REQUIRED_SCHEMA);

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

    public function decrementTsBalance(): array
    {
        $tsBalance = (int) $this->configArr['ts_balance'];
        if ($tsBalance > 0) {
            return $this->patch(Action::Update, array('ts_balance' => (string) ($tsBalance - 1)));
        }
        return $this->readOne();
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        // this select is executed every query, so we cache the result in memory
        if (apcu_exists(self::CACHE_KEY)) {
            return apcu_fetch(self::CACHE_KEY);
        }

        // cache miss, do sql query
        $sql = 'SELECT * FROM config';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        $config = $req->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP);

        // special case for remote_dir_config where we decrypt it in output so it can be used by external scripts
        if (!empty($config['remote_dir_config'])) {
            $config['remote_dir_config'][0] = TwigFilters::decrypt($config['remote_dir_config'][0]);
        }

        // we want key => value array
        $result = array_map(fn($v): mixed => $v[0], $config);
        apcu_store(self::CACHE_KEY, $result, self::CACHE_TTL_SECONDS);
        return $result;
    }

    /**
     * Used in sysconfig.php to update config values
     * NOTE: it is unlikely that someone with sysadmin level tries and edit requests to input incorrect values
     * so there is no real need for ensuring the values make sense, client side validation is enough this time
     *
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    #[Override]
    public function patch(Action $action, array $params): array
    {
        $passwords = array('smtp_password', 'ldap_password', 'ts_password', 'remote_dir_config');

        foreach ($passwords as $password) {
            if (isset($params[$password]) && !empty($params[$password])) {
                $params[$password] = Crypto::encrypt($params[$password], Key::loadFromAsciiSafeString(Env::asString('SECRET_KEY')));
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
                // prevent incorrect html in these two things
                if ($name === 'login_announcement' || $name === 'announcement') {
                    $value = Filter::body($value);
                }
                // for permissions, ensure at least one remains active
                if (str_starts_with($name, 'allow_permission_') && $value === '0') {
                    $this->assertAtLeastOneBasePermissionEnabled($name);
                }
                $req->bindParam(':value', $value);
                $req->bindParam(':name', $name);
                $this->Db->execute($req);
                // don't bother with ts_balance: will pollute the logs especially with automatic timestamping
                if ($name !== 'ts_balance') {
                    AuditLogs::create(new ConfigModified($name, (string) $this->configArr[$name], (string) $value));
                }
                $this->configArr[$name] = (string) $value;
            }
        }

        $this->bustCache();
        return $this->readAll();
    }

    #[Override]
    public function getApiPath(): string
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
                Key::loadFromAsciiSafeString(Env::asString('SECRET_KEY'))
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

    /**
     * Restore default values
     */
    #[Override]
    public function destroy(): bool
    {
        $sql = 'DELETE FROM config';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        $createResult = $this->create();
        $this->configArr = $this->readAll();
        return $createResult;
    }

    private function assertAtLeastOneBasePermissionEnabled(string $permissionName): void
    {
        // is current permission the one allowed
        $currentPermission = BasePermissions::fromKey($permissionName);
        // get the active base permissions
        $allowedPermissions = BasePermissions::getActiveBase($this->configArr);
        if (count($allowedPermissions) === 1 && array_key_exists($currentPermission->value, $allowedPermissions)) {
            throw new UnprocessableContentException('You must have at least one base permission active.');
        }
    }
}
