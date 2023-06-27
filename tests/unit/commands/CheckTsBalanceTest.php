<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Commands;

use Elabftw\Services\Email;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mailer\MailerInterface;

class CheckTsBalanceTest extends \PHPUnit\Framework\TestCase
{
    private Email $Email;

    private LoggerInterface $Logger;

    protected function setUp(): void
    {
        $this->Logger = new Logger('elabftw');
        // use NullHandler because we don't care about logs here
        $this->Logger->pushHandler(new NullHandler());
        $MockMailer = $this->createMock(MailerInterface::class);
        $this->Email = new Email($MockMailer, $this->Logger, 'phpunit@example.net');
    }

    public function testExecute(): void
    {
        $commandTester = new CommandTester(new CheckTsBalance(0, $this->Email));
        $commandTester->execute(array());
        $commandTester->assertCommandIsSuccessful();
    }

    public function testExecuteLow(): void
    {
        $commandTester = new CommandTester(new CheckTsBalance(12, $this->Email));
        $commandTester->execute(array());
        $commandTester->assertCommandIsSuccessful();
    }
}
