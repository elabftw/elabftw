<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class AuthTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $Request = Request::createFromGlobals();

        $Session = new Session();

        $Request->setSession($Session);
        $this->Auth = new Auth($Request, $Session);
    }

    public function testCheckCredentials()
    {
        $this->assertTrue($this->Auth->checkCredentials('phpunit@yopmail.com', 'phpunitftw'));
        $this->assertFalse($this->Auth->checkCredentials('phpunit@yopmail.com', 'wrong password'));
    }

    public function testCheckPasswordLength()
    {
        $this->assertTrue($this->Auth->checkPasswordLength('longpassword'));
        $this->expectException(ImproperActionException::class);
        $this->Auth->checkPasswordLength('short');
    }

    public function testLogin()
    {
        $this->assertTrue($this->Auth->login('phpunit@yopmail.com', 'phpunitftw'));
        $this->assertFalse($this->Auth->login('phpunit@yopmail.com', '0'));
    }
}
