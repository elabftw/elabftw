<?php
/**
 * \Elabftw\Elabftw\Config
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Exception;
use PDO;

/**
 * The general config table
 */
class Config
{
    /** @var Db $Db SQL Database */
    protected $Db;

    /** @var array $configArr the array with all config */
    public $configArr;

    /**
     * Get Db and load the configArr
     *
     */
    public function __construct()
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
     * Read the configuration values
     *
     * @return array
     */
    public function read(): array
    {
        $configArr = array();

        $sql = "SELECT * FROM config";
        $req = $this->Db->prepare($sql);
        $req->execute();
        $config = $req->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP);
        foreach ($config as $name => $value) {
            $configArr[$name] = $value[0];
        }
        return $configArr;
    }

    /**
     * Used in sysconfig.php to update config values
     *
     * @param array $post (conf_name => conf_value)
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @return bool the return value of execute queries
     */
    public function update(array $post): bool
    {
        $result = array();

        // do some data validation for some values
        /* TODO add upload button
        if (isset($post['stampcert'])) {
            $cert_chain = filter_var($post['stampcert'], FILTER_SANITIZE_STRING);
            if (!is_readable(realpath($cert_chain))) {
                throw new Exception('Cannot read provided certificate file.');
            }
        }
         */

        if (isset($post['stamppass']) && !empty($post['stamppass'])) {
            $post['stamppass'] = Crypto::encrypt($post['stamppass'], Key::loadFromAsciiSafeString(\SECRET_KEY));
        } elseif (isset($post['stamppass'])) {
            unset($post['stamppass']);
        }

        // sanitize canonical URL
        if (isset($post['url']) && !empty($post['url'])) {
            $post['url'] = filter_var($post['url'], FILTER_SANITIZE_URL);
        }

        if (isset($post['login_tries']) && Tools::checkId((int) $post['login_tries']) === false) {
            throw new Exception('Bad value for number of login attempts!');
        }
        if (isset($post['ban_time']) && Tools::checkId((int) $post['ban_time']) === false) {
            throw new Exception('Bad value for number of login attempts!');
        }

        // encrypt password
        if (isset($post['smtp_password']) && !empty($post['smtp_password'])) {
            $post['smtp_password'] = Crypto::encrypt($post['smtp_password'], Key::loadFromAsciiSafeString(SECRET_KEY));
        }

        // loop the array and update config
        foreach ($post as $name => $value) {
            $sql = "UPDATE config SET conf_value = :value WHERE conf_name = :name";
            $req = $this->Db->prepare($sql);
            $req->bindParam(':value', $value);
            $req->bindParam(':name', $name);
            $result[] = $req->execute();
        }

        return !\in_array(0, $result);
    }

    /**
     * Reset the timestamp password
     *
     * @return bool
     */
    public function destroyStamppass(): bool
    {
        $sql = "UPDATE config SET conf_value = NULL WHERE conf_name = 'stamppass'";
        $req = $this->Db->prepare($sql);
        return $req->execute();
    }

    /**
     * Insert the default values in config
     *
     * @return bool
     */
    public function populate(): bool
    {
        $Update = new Update($this);
        $schema = $Update->getRequiredSchema();

        $sql = "INSERT INTO `config` (`conf_name`, `conf_value`) VALUES
            ('admin_validate', '1'),
            ('ban_time', '60'),
            ('debug', '0'),
            ('lang', 'en_GB'),
            ('login_tries', '3'),
            ('mail_from', 'notconfigured@example.com'),
            ('mail_method', 'smtp'),
            ('proxy', ''),
            ('sendmail_path', '/usr/sbin/sendmail'),
            ('smtp_address', 'mail.smtp2go.com'),
            ('smtp_encryption', 'tls'),
            ('smtp_password', ''),
            ('smtp_port', '2525'),
            ('smtp_username', ''),
            ('stamplogin', ''),
            ('stamppass', ''),
            ('stampshare', '1'),
            ('stampprovider', 'http://zeitstempel.dfn.de/'),
            ('stampcert', 'app/dfn-cert/pki.dfn.pem'),
            ('stamphash', 'sha256'),
            ('saml_debug', '0'),
            ('saml_strict', '1'),
            ('saml_baseurl', NULL),
            ('saml_entityid', NULL),
            ('saml_acs_url', NULL),
            ('saml_acs_binding', NULL),
            ('saml_slo_url', NULL),
            ('saml_slo_binding', NULL),
            ('saml_nameidformat', NULL),
            ('saml_x509', NULL),
            ('saml_privatekey', NULL),
            ('saml_team', NULL),
            ('saml_email', NULL),
            ('saml_firstname', NULL),
            ('saml_lastname', NULL),
            ('local_login', '1'),
            ('local_register', '1'),
            ('anon_users', '0'),
            ('url', NULL),
            ('schema', :schema),
            ('open_science', '0'),
            ('open_team', NULL);";

        $req = $this->Db->prepare($sql);
        $req->bindParam(':schema', $schema);

        return $req->execute();
    }
}
