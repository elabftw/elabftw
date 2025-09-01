<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Elabftw\Env;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnprocessableContentException;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    private Config $Config;

    private array $setupValues;

    protected function setUp(): void
    {
        $this->Config = Config::getConfig();
        // TODO-Config: move to a new Config::getDecrypted() method.
        // decrypt encrypted keys from config
        $encryptedColumns = array('smtp_password', 'ldap_password', 'ts_password', 'remote_dir_config');
        $secretKey = Env::asString('SECRET_KEY');
        foreach ($encryptedColumns as $column) {
            if (!empty($this->Config->configArr[$column])) {
                $this->Config->configArr[$column] = Crypto::decrypt($this->Config->configArr[$column], Key::loadFromAsciiSafeString($secretKey));
            }
        }

        $this->setupValues = $this->Config->configArr;
    }

    protected function tearDown(): void
    {
        $this->Config->patch(Action::Update, $this->setupValues);
    }

    public function testRead(): void
    {
        $this->assertIsArray($this->Config->configArr);
        $this->assertIsArray($this->Config->readOne());
        $this->assertEquals('sha256', $this->Config->configArr['ts_hash']);
    }

    public function testGetApiPath(): void
    {
        $this->assertSame('api/v2/config/', $this->Config->getApiPath());
    }

    public function testPatch(): void
    {
        $post = array(
            'smtp_address' => 'smtp.mailgun.org',
            'smtp_encryption' => 'tls',
            'smtp_password' => 'yep',
            'smtp_port' => 587,
            'login_tries' => 15,
            'ts_login' => 'some-login',
            'ts_password' => 'some password!!',
            'ts_url' => 'https://tsa.example.org',
            'ts_cert' => '/path/to/cert.pem',
            'ts_authority' => 'custom',
            'allow_permission_team' => '1',
            'allow_permission_user' => '1',
        );

        $configArr = $this->Config->patch(Action::Update, $post);
        $this->assertEquals('/path/to/cert.pem', $configArr['ts_cert']);
        $this->assertEquals('custom', $configArr['ts_authority']);
        $this->assertEquals('1', $configArr['allow_permission_team']);
        $this->assertEquals('1', $configArr['allow_permission_user']);
    }

    public function testRestoreDefaults(): void
    {
        $this->assertTrue($this->Config->destroy());
    }

    public function testDecrementTsBalance(): void
    {
        $this->Config->patch(Action::Update, array('ts_balance' => 43));
        $result = $this->Config->decrementTsBalance();
        $this->assertEquals('42', $result['ts_balance']);
        // now set it to 0
        $this->Config->patch(Action::Update, array('ts_balance' => 0));
        $result = $this->Config->decrementTsBalance();
        $this->assertEquals('0', $result['ts_balance']);
    }

    public function testDsn(): void
    {
        $this->Config->patch(Action::Update, array('smtp_password' => Crypto::encrypt($this->Config->configArr['smtp_password'], Key::loadFromAsciiSafeString(Env::asString('SECRET_KEY')))));
        $this->assertIsString($this->Config->getDsn());
    }

    public function testPostAction(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Config->postAction(Action::Create, array());
    }

    public function testCannotPatchWithInvalidPermissions(): void
    {
        // Must have at least one permission
        $this->expectException(UnprocessableContentException::class);
        $this->Config->patch(Action::Update, array(
            'allow_permission_team' => '0',
            'allow_permission_user' => '0',
            'allow_permission_full' => '0',
            'allow_permission_organization' => '0',
            'allow_permission_useronly' => '0',
        ));
    }
}
