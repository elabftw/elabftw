<?php
namespace Elabftw\Elabftw;

use PDO;
use Elabftw\Core\Users;
use Elabftw\Core\Config;

class EmailTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->Email = new Email(new Config);
    }

    public function testGetMailer()
    {
        $this->assertInstanceOf('Swift_Mailer', $this->Email->getMailer());
    }
}
