<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users;

class ListBuilderTest extends \PHPUnit\Framework\TestCase
{
    protected function setup(): void
    {
        $this->ListBuilderExp = new ListBuilder(new Experiments(new Users(1, 1)));
        $this->ListBuilderDb = new ListBuilder(new Database(new Users(1, 1)));
    }

    public function testGetAutocomplete()
    {
        $this->assertTrue(is_array($this->ListBuilderExp->getAutocomplete('a')));
    }

    public function testGetMentionList()
    {
        $this->assertTrue(is_array($this->ListBuilderExp->getMentionList('a')));
        $this->assertTrue(is_array($this->ListBuilderDb->getMentionList('a')));
    }
}
