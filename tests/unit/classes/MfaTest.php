<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Services\MpdfQrProvider;
use RobThree\Auth\TwoFactorAuth;
use function strlen;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class MfaTest extends \PHPUnit\Framework\TestCase
{
    /** @var Session $Session Current session */
    public $Session;

    /** @var Request $Request Current request */
    private $Request;

    protected function setUp(): void
    {
        $this->Request = Request::createFromGlobals();

        $this->Session = new Session();

        $this->Request->setSession($this->Session);
        $this->Mfa = new Mfa($this->Request, $this->Session);
    }

    public function testNeedVerification()
    {
        $path = 'path/to/some/file.php';
        $this->assertFalse($this->Mfa->needVerification(2, $path));
        $this->assertFalse($this->Session->has('mfa_secret'));
        $this->assertFalse($this->Session->has('mfa_redirect'));

        $secret = 'EXAMPLE2FASECRET234567ABCDEFGHIJ';
        $this->assertTrue($this->Mfa->needVerification(1, $path));
        $this->assertEquals($this->Session->get('mfa_redirect'), $path);
        $this->assertEquals($this->Session->get('mfa_secret'), $secret);
    }

    public function testVerifyCode()
    {
        $secret = 'EXAMPLE2FASECRET234567ABCDEFGHIJ';
        $TwoFactorAuth = new TwoFactorAuth('eLabFTW', 6, 30, 'sha1', new MpdfQrProvider());
        $code = $TwoFactorAuth->getCode($secret);

        $this->Request->request->set('mfa_code', $code);
        $this->Session->set('mfa_secret', $secret);
        $this->assertTrue($this->Mfa->verifyCode());
        $this->assertTrue($this->Session->has('mfa_verified'));
        $this->Session->remove('mfa_verified');

        $this->Request->request->set('mfa_code', '123456');
        $this->assertFalse($this->Mfa->verifyCode());
        $this->assertFalse($this->Session->has('mfa_verified'));
    }

    public function testEnable()
    {
        $path = 'path/to/some/file.php';
        $this->assertEquals($this->Mfa->enable($path), '../../login.php');
        $this->assertEquals($this->Session->get('mfa_redirect'), $path);
        $this->assertEquals(strlen($this->Session->get('mfa_secret')), 32);
        $this->assertTrue($this->Session->get('enable_mfa'));

        $this->Session->remove('mfa_redirect');
        $this->Session->remove('mfa_secret');
        $this->Session->remove('enable_mfa');
    }

    public function testCleanup()
    {
        $path = 'path/to/some/file.php';
        $this->Mfa->enable($path);
        $this->assertEquals($this->Mfa->cleanup(), $path);
        $this->assertTrue($this->Session->has('enable_mfa'));
        $this->assertFalse($this->Session->has('mfa_secret'));
        $this->assertFalse($this->Session->has('mfa_redirect'));
        $this->Session->remove('enable_mfa');

        $this->Mfa->enable($path);
        $this->assertEquals($this->Mfa->cleanup(true), $path);
        $this->assertFalse($this->Session->has('enable_mfa'));
        $this->assertFalse($this->Session->has('mfa_secret'));
        $this->assertFalse($this->Session->has('mfa_redirect'));
    }

    public function testSaveSecret()
    {
        $path = 'path/to/some/file.php';
        $this->Session->set('userid', 1);
        $this->Mfa->enable($path);

        $this->assertEquals($this->Mfa->saveSecret(), $path);
        $this->assertFalse($this->Session->has('enable_mfa'));
        $this->assertFalse($this->Session->has('mfa_secret'));
        $this->assertFalse($this->Session->has('mfa_redirect'));
    }
}
