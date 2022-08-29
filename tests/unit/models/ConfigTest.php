<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    private Config $Config;

    protected function setUp(): void
    {
        $this->Config= Config::getConfig();
    }

    public function testRead(): void
    {
        $this->assertTrue(is_array($this->Config->configArr));
        $this->assertEquals('sha256', $this->Config->configArr['ts_hash']);
    }

    public function testGetPage(): void
    {
        $this->assertSame('api/v2/config/', $this->Config->getPage());
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
        );

        $configArr = $this->Config->patch(Action::Update, $post);
        $this->assertEquals('/path/to/cert.pem', $configArr['ts_cert']);
        $this->assertEquals('custom', $configArr['ts_authority']);
    }

    public function testRestoreDefaults(): void
    {
        $this->assertTrue($this->Config->destroy());
    }
}
