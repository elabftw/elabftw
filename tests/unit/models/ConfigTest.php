<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\ContentParams;

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

    public function testUpdate(): void
    {
        $this->assertTrue($this->Config->update(new ContentParams('some-login', 'ts_login')));
        $this->assertTrue($this->Config->update(new ContentParams('some-pass', 'ts_password')));
        $this->assertTrue($this->Config->update(new ContentParams('https://tsa.example.org', 'ts_url')));
        $this->assertTrue($this->Config->update(new ContentParams('/path/to/cert.pem', 'ts_cert')));
        $this->assertTrue($this->Config->update(new ContentParams('custom', 'ts_authority')));
    }

    public function testUpdateAll(): void
    {
        $post = array(
            'smtp_address' => 'smtp.mailgun.org',
            'smtp_encryption' => 'tls',
            'smtp_password' => 'yep',
            'smtp_port' => 587,
            'login_tries' => 15,
        );

        $this->Config->updateAll($post);
    }

    public function testDestroyStamppass(): void
    {
        $this->Config->destroyStamppass();
    }

    public function testRestoreDefaults(): void
    {
        $this->Config->destroy();
    }
}
