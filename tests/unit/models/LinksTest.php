<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\IdParams;
use function is_array;

class LinksTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Experiments = new Experiments(new Users(1, 1), 1);
    }

    public function testCreateReadDestroy()
    {
        $id = $this->Experiments->Links->create(new IdParams(1));
        $links = $this->Experiments->Links->read();
        $this->assertTrue(is_array($links));
        $this->Experiments->Links->setId($id);
        $this->Experiments->Links->destroy();
    }
}
