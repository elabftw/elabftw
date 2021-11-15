<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Users;

class ListBuilderTest extends \PHPUnit\Framework\TestCase
{
    private ListBuilder $ListBuilderExp;

    private ListBuilder $ListBuilderDb;

    protected function setUp(): void
    {
        $this->ListBuilderExp = new ListBuilder(new Experiments(new Users(1, 1)));
        $this->ListBuilderDb = new ListBuilder(new Items(new Users(1, 1)));
    }

    public function testGetAutocomplete(): void
    {
        $this->assertTrue(is_array($this->ListBuilderExp->getAutocomplete('a')));
        $ListBuilderExpFiltered = new ListBuilder(new Experiments(new Users(1, 1)), 1);
        $ListBuilderDbFiltered = new ListBuilder(new Items(new Users(1, 1)), 1);
        $this->assertTrue(is_array($ListBuilderExpFiltered->getAutocomplete('a')));
        $this->assertTrue(is_array($ListBuilderDbFiltered->getAutocomplete('a')));
    }

    public function testGetMentionList(): void
    {
        $this->assertTrue(is_array($this->ListBuilderExp->getMentionList('a')));
        $this->assertTrue(is_array($this->ListBuilderDb->getMentionList('a')));
    }
}
