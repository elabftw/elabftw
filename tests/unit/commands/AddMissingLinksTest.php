<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Commands;

use Symfony\Component\Console\Tester\CommandTester;

class AddMissingLinksTest extends \PHPUnit\Framework\TestCase
{
    public function testExecute(): void
    {
        $commandTester = new CommandTester(new AddMissingLinks());
        $commandTester->execute(array());
        $commandTester->assertCommandIsSuccessful();
    }
}
