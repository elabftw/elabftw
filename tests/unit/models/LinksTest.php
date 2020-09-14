<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\ParamsProcessor;
use function is_array;

class LinksTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Users = new Users(1);
        $this->Experiments = new Experiments($this->Users, 1);
    }

    public function testCreateReadDestroy()
    {
        $this->Experiments->Links->create(new ParamsProcessor(array('id' => 1)));
        $links = $this->Experiments->Links->read();
        $this->assertTrue(is_array($links));
        $last = array_pop($links);
        $this->Experiments->Links->destroy((int) $last['linkid']);
    }
}
