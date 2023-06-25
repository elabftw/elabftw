<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Commands;

use Elabftw\Storage\Fixtures;
use Symfony\Component\Console\Tester\CommandTester;

class ImportResourcesTest extends \PHPUnit\Framework\TestCase
{
    public function testExecute(): void
    {
        $commandTester = new CommandTester(new ImportResources(new Fixtures()));
        $commandTester->execute(array(
            'category_id' => '1',
            'userid' => '1',
            'file' => 'single-experiment.eln',
        ));

        $commandTester->assertCommandIsSuccessful();
    }
}
