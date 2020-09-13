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
    private $Session;

    /** @var Request $Request Current request */
    private $Request;

    /** @var string $testPath Some file path */
    private $testPath;

    /** @var string $secret The 2FA test secret */
    private $secret;

    protected function setUp(): void
    {
        $this->testPath = 'path/to/some/file.php';
        $this->secret = 'EXAMPLE2FASECRET234567ABCDEFGHIJ';

        $this->Request = Request::createFromGlobals();
        $this->Session = new Session();

        $this->Request->setSession($this->Session);
        $this->Mfa = new Mfa($this->Request, $this->Session);
    }

    protected function tearDown(): void
    {
        $this->Session->invalidate();
    }

    public function testEnable()
    {
        $this->assertEquals('../../login.php', $this->Mfa->enable($this->testPath));
        $this->assertEquals($this->testPath, $this->Session->get('mfa_redirect'));
        $this->assertEquals(32, strlen($this->Session->get('mfa_secret')));
        $this->assertTrue($this->Session->get('enable_mfa'));
    }

    /**
     * @depends testEnable
     */
    public function testCleanupFalse()
    {
        $this->Mfa->enable($this->testPath);

        $this->assertEquals($this->testPath, $this->Mfa->cleanup());
        $this->assertTrue($this->Session->has('enable_mfa'));
        $this->assertFalse($this->Session->has('mfa_secret'));
        $this->assertFalse($this->Session->has('mfa_redirect'));
    }

    /**
     * @depends testEnable
     */
    public function testCleanupTrue()
    {
        $this->Mfa->enable($this->testPath);

        $this->assertEquals($this->testPath, $this->Mfa->cleanup(true));
        $this->assertFalse($this->Session->has('enable_mfa'));
        $this->assertFalse($this->Session->has('mfa_secret'));
        $this->assertFalse($this->Session->has('mfa_redirect'));
    }

    /**
     * @depends testEnable
     * @depends testCleanupTrue
     */
    public function testAbortEnable()
    {
        $this->Mfa->enable($this->testPath);
        $this->assertEquals($this->testPath, $this->Mfa->abortEnable());
    }

    /**
     * @depends testCleanupTrue
     */
    public function testSaveSecret()
    {
        $this->Session->set('userid', 1);
        $this->Session->set('mfa_secret', $this->secret);
        $this->Session->set('enable_mfa', true);
        $this->Session->set('mfa_redirect', $this->testPath);

        $this->assertEquals($this->testPath, $this->Mfa->saveSecret());
    }

    /**
     * @depends testSaveSecret
     */
    public function testDisable()
    {
        $this->Session->set('userid', 3);
        $this->Session->set('mfa_secret', $this->secret);
        $this->Session->set('mfa_verified', time());
        $this->Mfa->saveSecret();

        $this->assertTrue($this->Mfa->disable(3));
        $this->assertFalse($this->Session->has('mfa_verified'));
    }

    public function testNeedVerificationFalse()
    {
        $this->assertFalse($this->Mfa->needVerification(2, $this->testPath));
        $this->assertFalse($this->Session->has('mfa_secret'));
        $this->assertFalse($this->Session->has('mfa_redirect'));
    }

    public function testNeedVerificationTrue()
    {
        $this->assertTrue($this->Mfa->needVerification(1, $this->testPath));
        $this->assertEquals($this->testPath, $this->Session->get('mfa_redirect'));
        $this->assertEquals($this->secret, $this->Session->get('mfa_secret'));
    }

    public function testVerifyCodeTrue()
    {
        $TwoFactorAuth = new TwoFactorAuth('eLabFTW', 6, 30, 'sha1', new MpdfQrProvider());
        $this->Request->request->set('mfa_code', $TwoFactorAuth->getCode($this->secret));
        $this->Session->set('mfa_secret', $this->secret);

        $this->assertTrue($this->Mfa->verifyCode());
        $this->assertTrue($this->Session->has('mfa_verified'));
    }

    public function testVerifyCodeFalse()
    {
        $this->Request->request->set('mfa_code', '123456');
        $this->Session->set('mfa_secret', $this->secret);

        $this->assertFalse($this->Mfa->verifyCode());
        $this->assertFalse($this->Session->has('mfa_verified'));
    }
}
