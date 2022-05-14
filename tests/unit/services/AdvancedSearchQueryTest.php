<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\TeamGroupParams;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Users;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;

class AdvancedSearchQueryTest extends \PHPUnit\Framework\TestCase
{
    private int $groupID;

    private TeamGroups $TeamGroups;

    private array $visibilityList;

    private array $groups;

    protected function setUp(): void
    {
        $this->TeamGroups = new TeamGroups(new Users(1, 1));
        $this->groupID = $this->TeamGroups->create(new ContentParams('Group Name'));
        $this->TeamGroups->id = $this->groupID;
        $this->TeamGroups->update(new TeamGroupParams('', 'member', array('how' => 'add', 'group' => $this->groupID, 'userid' => 1)));

        $this->visibilityList = $this->TeamGroups->getVisibilityList();
        $this->groups = $this->TeamGroups->readGroupsWithUsersFromUser();
    }

    protected function tearDown(): void
    {
        $this->TeamGroups->update(new TeamGroupParams('', 'member', array('how' => 'rm', 'group' => $this->groupID, 'userid' => 1)));
        $this->TeamGroups->destroy();
    }

    public function testGetWhereClause(): void
    {
        $query = ' TEST TEST1 AND TEST2 OR TEST3 NOT TEST4 & TEST5';
        $query .= ' | TEST6 AND ! TEST7 (TEST8 or TEST9) "T E S T 1 0"';
        $query .= ' \'T E S T 1 1\' "chinese 汉语 漢語 中文" "japanese 日本語 ひらがな 平仮名 カタカナ 片仮名"';
        $query .= ' attachment:0 author:"Phpunit TestUser" body:"some text goes here"';
        $query .= ' elabid:7bebdd3512dc6cbee0b1 locked:yes rating:5 rating:unrated';
        $query .= ' status:"only meaningful with experiments but no error"';
        $query .= ' timestamped: timestamped:true title:"very cool experiment" visibility:%me';
        $query .= ' date:>2020.06,21 date:2020/06-21..20201231';
        $query .= ' group:"Group Name"';
        $query .= ' attachment:"hello world"';

        $advancedSearchQuery = new AdvancedSearchQuery($query, new VisitorParameters(
            'experiments',
            $this->visibilityList,
            $this->groups,
        ));
        $whereClause = $advancedSearchQuery->getWhereClause();
        $this->assertIsArray($whereClause);
        $this->assertStringStartsWith(' AND (((entity.body LIKE :', $whereClause['where']);
        $this->assertStringEndsWith(')))', $whereClause['where']);

        $query = 'category:"only meaningful with items but no error"';
        $advancedSearchQuery = new AdvancedSearchQuery($query, new VisitorParameters(
            'items',
            $this->visibilityList,
            $this->groups,
        ));
        $whereClause = $advancedSearchQuery->getWhereClause();
        $this->assertStringStartsWith(' AND (categoryt.name LIKE :', $whereClause['where']);
        $this->assertStringEndsWith(')', $whereClause['where']);
    }

    public function testSyntaxError(): void
    {
        $query = 'AND AND AND';

        $advancedSearchQuery = new AdvancedSearchQuery($query, new VisitorParameters(
            'experiments',
            $this->visibilityList,
            $this->groups,
        ));
        $advancedSearchQuery->getWhereClause();
        $this->assertStringStartsWith('Line 1, Column ', $advancedSearchQuery->getException());
    }

    public function testComplexityLimit(): void
    {
        $query = 'TEST TEST1';

        // Depth of abstract syntax tree is set to 1 with the last parameter
        $advancedSearchQuery = new AdvancedSearchQuery($query, new VisitorParameters(
            'experiments',
            $this->visibilityList,
            $this->groups,
        ), 1);
        $advancedSearchQuery->getWhereClause();
        $this->assertEquals('Query is too complex!', $advancedSearchQuery->getException());
    }

    public function testFieldValidatorInvalidFields(): void
    {
        $visInput = 'noValidInput';
        $from = '20210101';
        $to = '20200101';
        $query = 'visibility:' . $visInput;
        $query .= ' date:' . $from . '..' . $to;
        $query .= ' group:"does not exist"';
        $query .= ' category:"only works for items"';

        $advancedSearchQuery = new AdvancedSearchQuery($query, new VisitorParameters(
            'experiments',
            $this->visibilityList,
            $this->groups,
        ));
        $advancedSearchQuery->getWhereClause();
        $this->assertStringStartsWith('visibility:' . $visInput . '. Valid values are ', $advancedSearchQuery->getException());
        $this->assertStringContainsString('date:' . $from . '..' . $to . '. Second date needs to be equal or greater than first date.', $advancedSearchQuery->getException());
        $this->assertStringContainsString('group:', $advancedSearchQuery->getException());
        $this->assertStringEndsWith('category: is only allowed when searching in database.', $advancedSearchQuery->getException());

        $query = 'timestamped:true';
        $query .= ' status:"only works for experiments"';

        $advancedSearchQuery = new AdvancedSearchQuery($query, new VisitorParameters(
            'itmes',
            $this->visibilityList,
            $this->groups,
        ));
        $advancedSearchQuery->getWhereClause();
        $this->assertStringStartsWith('timestamped: is only allowed when searching in experiments.', $advancedSearchQuery->getException());
        $this->assertStringEndsWith('status: is only allowed when searching in experiments.', $advancedSearchQuery->getException());
    }
}
