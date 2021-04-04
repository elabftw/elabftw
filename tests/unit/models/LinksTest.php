<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\CreateLink;
use Elabftw\Elabftw\DestroyParams;
use function is_array;

class LinksTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Experiments = new Experiments(new Users(1, 1), 1);
    }

    public function testCreateReadDestroy()
    {
        $this->Experiments->Links->create(new CreateLink(1));
        $links = $this->Experiments->Links->read();
        $this->assertTrue(is_array($links));
        $last = array_pop($links);
        $this->Experiments->Links->destroy(new DestroyParams((int) $last['linkid']));
    }
}
