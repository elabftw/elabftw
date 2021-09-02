<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

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
        $this->assertEquals('sha256', $this->Config->configArr['stamphash']);
    }

    public function testUpdate(): void
    {
        $post = array(
            'smtp_address' => 'smtp.mailgun.org',
            'smtp_encryption' => 'tls',
            'smtp_password' => 'yep',
            'smtp_port' => 587,
            'stampcert' => 'src/dfn-cert/pki.dfn.pem',
            'stamppass' => '',
            'login_tries' => 15,
        );

        $this->Config->update($post);
    }

    public function testDestroyStamppass(): void
    {
        $this->Config->destroyStamppass();
    }

    public function testRestoreDefaults(): void
    {
        $this->Config->restoreDefaults();
    }
}
