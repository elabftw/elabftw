<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Commands;

use Elabftw\Models\Config;
use Elabftw\Storage\Fixtures;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * This file bundles a bunch of commands test together, because most of them are quite short
 */
class CommandsTest extends \PHPUnit\Framework\TestCase
{
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
}
