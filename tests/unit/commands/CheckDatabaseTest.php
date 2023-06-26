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
use Symfony\Component\Console\Tester\CommandTester;

class CheckDatabaseTest extends \PHPUnit\Framework\TestCase
{
    public function testExecute(): void
    {
        $Config = Config::getConfig();
        $commandTester = new CommandTester(new CheckDatabase((int) $Config->configArr['schema']));
        $commandTester->execute(array());
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('No upgrade required.', $output);
    }

    public function testExecuteNeedsUpdate(): void
    {
        $commandTester = new CommandTester(new CheckDatabase(42));
        $statusCode = $commandTester->execute(array());
        $this->assertSame(1, $statusCode);
        $this->assertStringContainsString('An upgrade is required.', $commandTester->getDisplay());
    }
}
