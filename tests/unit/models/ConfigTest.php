<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Exception;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Config= new Config();
    }

    public function testRead()
    {
        $this->assertTrue(is_array($this->Config->configArr));
        $this->assertEquals('sha256', $this->Config->configArr['stamphash']);
    }

    public function testUpdate()
    {
        $post = array(
            'smtp_address' => 'smtp.mailgun.org',
            'smtp_encryption' => 'tls',
            'smtp_password' => 'yep',
            'smtp_port' => 587,
            'stampcert' => 'src/dfn-cert/pki.dfn.pem',
            'stamppass' => '',
            'login_tries' => 15,
            'ban_time' => 42,
        );

        $this->Config->update($post);
        // now try bad path to cert
        /* TODO
        $post = array('stampcert' => 'invalid/path');
        $this->expectException(\Exception::class);
        $this->Config->update($post);
         */
        // try bad value for ban_time
        $post = array('ban_time' => 'invalid');
        $this->expectException(Exception::class);
        $this->Config->update($post);
        // try bad value for login_tries
        $post = array('login_tries' => 'invalid');
        $this->expectException(Exception::class);
        $this->Config->update($post);
        // try with no password
        $post = array('smtp_password' => '');
        $this->expectException(Exception::class);
        $this->Config->update($post);
    }

    public function testDestroyStamppass()
    {
        $this->Config->destroyStamppass();
    }

    public function testRestoreDefaults()
    {
        $this->Config->restoreDefaults();
    }
}
