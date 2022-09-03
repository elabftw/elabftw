<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Users;

class ElabidFinderTest extends \PHPUnit\Framework\TestCase
{
    public function testFindInExperiments(): void
    {
        $Entity = new Experiments(new Users(1, 1));
        $id = $Entity->create(-1);
        $Entity->setId($id);
        $elabid = $Entity->entityData['elabid'];
        $finder = new ElabidFinder('/experiments.php', $elabid);
        $this->assertEquals(1, $finder->findTeam());
    }

    public function testFindInItems(): void
    {
        $Entity = new Items(new Users(1, 1));
        $id = $Entity->create(1);
        $Entity->setId($id);
        $elabid = $Entity->entityData['elabid'];
        $finder = new ElabidFinder('/database.php', $elabid);
        $this->assertEquals(1, $finder->findTeam());
    }
}
