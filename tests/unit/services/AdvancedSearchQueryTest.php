<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

class AdvancedSearchQueryTest extends \PHPUnit\Framework\TestCase
{
    public function testGetWhereClause(): void
    {
        $query = ' TEST TEST1 AND TEST2 OR TEST3 NOT TEST4 & TEST5';
        $query .= ' | TEST6 AND ! TEST7 (TEST8 or TEST9) "T E S T 1 0"';
        $query .= ' \'T E S T 1 1\' "chinese 汉语 漢語 中文" "japanese 日本語 ひらがな 平仮名 カタカナ 片仮名" ';

        $advancedSearchQuery = (new AdvancedSearchQuery($query, 'body'));
        $whereClause = $advancedSearchQuery->getWhereClause();
        $this->assertIsArray($whereClause);
        $this->assertStringStartsWith(' AND ((entity.body LIKE :', $whereClause['where']);
        $this->assertStringEndsWith(')))', $whereClause['where']);
    }

    public function testSyntaxError(): void
    {
        $query = 'AND AND AND';

        $advancedSearchQuery = (new AdvancedSearchQuery($query, 'body'));
        $advancedSearchQuery->getWhereClause();
        $this->assertStringStartsWith('Column ', $advancedSearchQuery->getException());
    }

    public function testComplexityLimit(): void
    {
        $query = 'TEST TEST1';

        $advancedSearchQuery = (new AdvancedSearchQuery($query, 'body', 1));
        $advancedSearchQuery->getWhereClause();
        $this->assertEquals('Query is too complex.', $advancedSearchQuery->getException());
    }
}
