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
namespace Elabftw\Elabftw;

use PDO;
use Exception;
use Defuse\Crypto\Crypto as Crypto;
use Defuse\Crypto\Key as Key;

/**
 * The general config table
 */
class Config
{
    /** db connection */
    protected $pdo;

    /** the array with all config */
    public $configArr;

    /**
     * Get pdo and load the configArr
     *
     */
    public function __construct()
    {
        $this->pdo = Db::getConnection();
        $this->configArr = $this->read();
    }

    /**
     * Read the configuration values
     *
     * @return array
     */
    public function read()
    {
        $final = array();

        $sql = "SELECT * FROM config";
        $req = $this->pdo->prepare($sql);
        $req->execute();
        $config = $req->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP);
        foreach ($config as $name => $value) {
            $final[$name] = $value[0];
        }
        return $final;
    }

    /**
     * Used in sysconfig.php to update config values
     *
     * @param array $post (conf_name => conf_value)
     * @return bool the return value of execute queries
     */
    public function update($post)
    {
        $result = array();

        // do some data validation for some values
        if (isset($post['stampcert'])) {
            $cert_chain = filter_var($post['stampcert'], FILTER_SANITIZE_STRING);
            if (!is_readable(realpath(ELAB_ROOT . $cert_chain))) {
                throw new Exception('Cannot read provided certificate file.');
            }
        }

        if (isset($post['stamppass']) && !empty($post['stamppass'])) {
            $post['stamppass'] = Crypto::encrypt($post['stamppass'], Key::loadFromAsciiSafeString(SECRET_KEY));
        } elseif (isset($post['stamppass'])) {
            unset($post['stamppass']);
        }

        if (isset($post['login_tries']) && Tools::checkId($post['login_tries']) === false) {
            throw new Exception('Bad value for number of login attempts!');
        }
        if (isset($post['ban_time']) && Tools::checkId($post['ban_time']) === false) {
            throw new Exception('Bad value for number of login attempts!');
        }

        // encrypt password
        if (isset($post['smtp_password']) && !empty($post['smtp_password'])) {
            $post['smtp_password'] = Crypto::encrypt($post['smtp_password'], Key::loadFromAsciiSafeString(SECRET_KEY));
        // we might receive a set but empty smtp_password, so ignore it
        } elseif (empty($post['smtp_password'])) {
            unset($post['smtp_password']);
        }

        // loop the array and update config
        foreach ($post as $name => $value) {
            $sql = "UPDATE config SET conf_value = :value WHERE conf_name = :name";
            $req = $this->pdo->prepare($sql);
            $req->bindParam(':value', $value);
            $req->bindParam(':name', $name);
            $result[] = $req->execute();
        }

        return !in_array(0, $result);
    }


    /**
     * Reset the timestamp password
     *
     * @return bool
     */
    public function destroyStamppass()
    {
        $sql = "UPDATE config SET conf_value = NULL WHERE conf_name = 'stamppass'";
        $req = $this->pdo->prepare($sql);
        return $req->execute();
    }

    /**
     * Reset the config to default values
     *
     * @return bool
     */
    public function reset()
    {
        $defaultConf = array(
            "admin_validate" => '1',
            "ban_time" => '60',
            "debug" => '0',
            "lang" => 'en_GB',
            "login_tries" => '3',
            "mail_from" => 'notconfigured@example.com',
            "mail_method" => 'sendmail',
            "proxy" => '',
            "sendmail_path" => '/usr/sbin/sendmail',
            "smtp_address" => 'mail.smtp2go.com',
            "smtp_encryption" => 'tls',
            "smtp_password" => '',
            "smtp_port" => '2525',
            "smtp_username" => '',
            "stamplogin" => '',
            "stamppass" => '',
            "stampshare" => '1',
            "stampprovider" => 'http://zeitstempel.dfn.de/',
            "stampcert" => 'app/dfn-cert/pki.dfn.pem',
            "stamphash" => 'sha256',
            "schema" => '14');

        return $this->Update($defaultConf);
    }
}
