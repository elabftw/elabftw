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

use Elabftw\Elabftw\PermissionsHelper;
use Elabftw\Enums\Action;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Users;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;

class AdvancedSearchQueryTest extends \PHPUnit\Framework\TestCase
{
    private int $groupId;

    private TeamGroups $TeamGroups;

    private array $visibilityList;

    private array $groups;

    protected function setUp(): void
    {
        $this->TeamGroups = new TeamGroups(new Users(1, 1));
        $this->groupId = $this->TeamGroups->postAction(Action::Create, array('name' => 'Group Name'));
        $this->TeamGroups->setId($this->groupId);
        $this->TeamGroups->patch(Action::Update, array('userid' => 1, 'how' => Action::Add->value));

        $PermissionsHelper = new PermissionsHelper();
        $this->visibilityList = $PermissionsHelper->getExtendedSearchAssociativeArray();
        $this->groups = $this->TeamGroups->readGroupsWithUsersFromUser();
    }

    protected function tearDown(): void
    {
        $this->TeamGroups->patch(Action::Update, array('userid' => 1, 'how' => Action::Unreference->value));
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
        $query .= ' timestamped_at:2022.12.01..2022-12-31';
        $query .= ' timestamped_at:2022/12/09';
        $query .= ' timestamped_at:!=2022,12,09';
        $query .= ' created_at:>2022,12.09';
        $query .= ' locked_at:<20221209';

        $advancedSearchQuery = new AdvancedSearchQuery($query, new VisitorParameters(
            'experiments',
            $this->visibilityList,
            $this->groups,
        ));
        $whereClause = $advancedSearchQuery->getWhereClause();
        $this->assertIsArray($whereClause);
        $this->assertStringStartsWith(' AND (((entity.title LIKE :', $whereClause['where']);
        $this->assertStringEndsWith(')))', $whereClause['where']);

        $query = 'category:"only meaningful with items but no error"';
        $advancedSearchQuery = new AdvancedSearchQuery($query, new VisitorParameters(
            'items',
            $this->visibilityList,
            $this->groups,
        ));
        $whereClause = $advancedSearchQuery->getWhereClause();
        $this->assertStringStartsWith(' AND (categoryt.title LIKE :', $whereClause['where']);
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
        $query .= ' timestamped_at:' . $from . '..' . $to;
        $query .= ' created_at:19700101';
        $query .= ' locked_at:20221209..20380119';
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
        $this->assertStringContainsString('timestamped_at:' . $from . '..' . $to . '. Second date needs to be equal or greater than first date.', $advancedSearchQuery->getException());
        $this->assertStringContainsString('created_at: Date needs to be between 1970-01-02 and 2038-01-18.', $advancedSearchQuery->getException());
        $this->assertStringContainsString('locked_at: Date needs to be between 1970-01-02 and 2038-01-18.', $advancedSearchQuery->getException());
        $this->assertStringContainsString('group:', $advancedSearchQuery->getException());
        $this->assertStringEndsWith('category: is only allowed when searching in database.', $advancedSearchQuery->getException());

        $query = 'timestamped:true';
        $query .= ' timestamped_at:20221209';
        $query .= ' status:"only works for experiments"';

        $advancedSearchQuery = new AdvancedSearchQuery($query, new VisitorParameters(
            'itmes',
            $this->visibilityList,
            $this->groups,
        ));
        $advancedSearchQuery->getWhereClause();
        $this->assertStringStartsWith('timestamped: is only allowed when searching in experiments.', $advancedSearchQuery->getException());
        $this->assertStringContainsString('timestamped_at: is only allowed when searching in experiments.', $advancedSearchQuery->getException());
        $this->assertStringEndsWith('status: is only allowed when searching in experiments.', $advancedSearchQuery->getException());
    }
}
