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
        $this->assertIsArray($this->ListBuilderExp->getAutocomplete('a'));
        $this->assertIsArray($this->ListBuilderExp->getAutocomplete('a', '1'));
        $this->assertIsArray($this->ListBuilderDb->getAutocomplete('a', '1'));
    }

    public function testGetMentionList(): void
    {
        $this->assertIsArray($this->ListBuilderExp->getMentionList('a'));
        $this->assertIsArray($this->ListBuilderDb->getMentionList('a'));
    }
}
