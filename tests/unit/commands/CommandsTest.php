<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Commands;

use Elabftw\Models\Config;
use Elabftw\Services\Email;
use Elabftw\Services\MfaHelperTest;
use Elabftw\Storage\Fixtures;
use Elabftw\Storage\Memory;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mailer\MailerInterface;

/**
 * This file bundles a bunch of commands test together, because most of them are quite short
 */
class CommandsTest extends \PHPUnit\Framework\TestCase
{
    private Email $Email;

    protected function setUp(): void
    {
        $Logger = new Logger('elabftw');
        // use NullHandler because we don't care about logs here
        $Logger->pushHandler(new NullHandler());
        $MockMailer = $this->createMock(MailerInterface::class);
        $this->Email = new Email($MockMailer, $Logger, 'toto@yopmail.com');
    }

    public function testCheckTsBalance(): void
    {
        $commandTester = new CommandTester(new CheckTsBalance(0, $this->Email));
        $commandTester->execute(array());
        $commandTester->assertCommandIsSuccessful();
    }

    public function testCheckTsBalanceLow(): void
    {
        $commandTester = new CommandTester(new CheckTsBalance(12, $this->Email));
        $commandTester->execute(array());
        $commandTester->assertCommandIsSuccessful();
    }

    public function testExecuteGenSecretKey(): void
    {
        $commandTester = new CommandTester(new GenSecretKey());
        $commandTester->execute(array());
        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('def', $commandTester->getDisplay());
    }

    public function testExecuteAddMissingLinks(): void
    {
        $commandTester = new CommandTester(new AddMissingLinks());
        $commandTester->execute(array());
        $commandTester->assertCommandIsSuccessful();
    }

    public function testExecuteCleanDatabase(): void
    {
        $commandTester = new CommandTester(new CleanDatabase());
        $commandTester->execute(array());
        $commandTester->assertCommandIsSuccessful();
    }

    public function testExecuteExperimentsTimestamp(): void
    {
        $commandTester = new CommandTester(new ExperimentsTimestamp());
        $commandTester->execute(array(
            'user' => '1',
            '--dry-run' => true,
        ));
        $commandTester->assertCommandIsSuccessful();
    }

    public function testForceSchema(): void
    {
        $commandTester = new CommandTester(new ForceSchema());
        $Config = Config::getConfig();
        $commandTester->execute(array('schema' => $Config->configArr['schema']));
        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Changing schema to', $commandTester->getDisplay());
    }

    public function testGenCache(): void
    {
        $commandTester = new CommandTester(new GenCache());
        $commandTester->execute(array());
        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Success', $commandTester->getDisplay());
    }

    public function testGenSchema(): void
    {
        $commandTester = new CommandTester(new GenSchema((new Memory())->getFs()));
        $commandTester->execute(array());
        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Created file', $commandTester->getDisplay());
    }

    public function testInstall(): void
    {
        $commandTester = new CommandTester(new Install());
        $commandTester->execute(array());
        $commandTester->assertCommandIsSuccessful();
    }

    public function testMfaCode(): void
    {
        $commandTester = new CommandTester(new MfaCode());
        $commandTester->execute(array('secret' => MfaHelperTest::SECRET));
        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('2FA code:', $commandTester->getDisplay());
    }

    public function testPruneExperiments(): void
    {
        $commandTester = new CommandTester(new PruneExperiments());
        $commandTester->execute(array());
        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Removed', $commandTester->getDisplay());
    }

    public function testPruneItems(): void
    {
        $commandTester = new CommandTester(new PruneItems());
        $commandTester->execute(array());
        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Removed', $commandTester->getDisplay());
    }

    public function testPruneRevisions(): void
    {
        $commandTester = new CommandTester(new PruneRevisions());
        $commandTester->execute(array());
        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Revisions pruning', $commandTester->getDisplay());
    }

    public function testPruneUploads(): void
    {
        $commandTester = new CommandTester(new PruneUploads());
        $commandTester->execute(array());
        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Removed', $commandTester->getDisplay());
    }

    public function testRevertSchema(): void
    {
        $commandTester = new CommandTester(new RevertSchema((new Fixtures())->getFs()));
        $commandTester->execute(array('number' => '42'));
        $commandTester->assertCommandIsSuccessful();
    }

    public function testExecuteCacheClear(): void
    {
        $commandTester = new CommandTester(new CacheClear());
        $commandTester->execute(array());
        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Cache cleared!', $commandTester->getDisplay());
    }

    public function testExecuteCheckDatabase(): void
    {
        $Config = Config::getConfig();
        $commandTester = new CommandTester(new CheckDatabase((int) $Config->configArr['schema']));
        $commandTester->execute(array());
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('No upgrade required.', $output);
    }

    public function testExecuteCheckDatabaseNeedsUpdate(): void
    {
        $commandTester = new CommandTester(new CheckDatabase(42));
        $statusCode = $commandTester->execute(array());
        $this->assertSame(1, $statusCode);
        $this->assertStringContainsString('An upgrade is required.', $commandTester->getDisplay());
    }

    public function testExecuteImportResources(): void
    {
        $commandTester = new CommandTester(new ImportResources(new Fixtures()));
        $commandTester->execute(array(
            'category_id' => '1',
            'userid' => '1',
            'file' => 'single-experiment.eln',
        ));

        $commandTester->assertCommandIsSuccessful();
    }

    public function testExecuteImportUser(): void
    {
        $commandTester = new CommandTester(new ImportUser(new Fixtures()));
        $commandTester->execute(array(
            'userid' => '1',
            'file' => 'single-experiment.eln',
        ));

        $commandTester->assertCommandIsSuccessful();
    }

    public function testExecuteExportUser(): void
    {
        $commandTester = new CommandTester(new ExportUser(new Memory()));
        $commandTester->execute(array(
            'userid' => '1',
        ));

        $commandTester->assertCommandIsSuccessful();
    }

    public function testExecuteExportResources(): void
    {
        $commandTester = new CommandTester(new ExportResources(new Memory()));
        $commandTester->execute(array(
            'category_id' => '1',
            'userid' => '1',
        ));

        $commandTester->assertCommandIsSuccessful();
    }

    public function testSendNotifications(): void
    {
        $commandTester = new CommandTester(new SendNotifications($this->Email));
        $commandTester->execute(array(), array('verbosity' => OutputInterface::VERBOSITY_VERBOSE));
        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Sent', $commandTester->getDisplay());
    }

    public function testSnapfingers(): void
    {
        $commandTester = new CommandTester(new SnapFingers());
        $commandTester->execute(array());
        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Snap', $commandTester->getDisplay());
    }

    public function testUploadsCheck(): void
    {
        $commandTester = new CommandTester(new CheckUploads());
        $commandTester->execute(array());
        $commandTester->assertCommandIsSuccessful();
    }
}
