<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\Sql;

class DatabaseInstallerTest extends \PHPUnit\Framework\TestCase
{
    public function testInstall(): void
    {
        $Sql = $this->createMock(Sql::class);
        $DatabaseInstaller = new DatabaseInstaller($Sql);
        $DatabaseInstaller->install();
    }
}
