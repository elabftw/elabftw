<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Codeception\Attribute\DataProvider;
use Elabftw\Exceptions\ForbiddenException;
use PHPUnit\Framework\TestCase;

final class DbReadOnlyTest extends TestCase
{
    private Db $Db;

    protected function setUp(): void
    {
        $this->Db = Db::getConnection();
        $this->Db->setReadOnly(true);
    }

    // the connection is a singleton: never leak the flag to the other tests
    protected function tearDown(): void
    {
        $this->Db->setReadOnly(false);
    }

    #[DataProvider('provideReadQueries')]
    public function testReadQueriesRun(string $sql): void
    {
        $this->assertTrue($this->Db->execute($this->Db->prepare($sql)));
        $this->assertNotFalse($this->Db->q($sql)->fetch());
    }

    public static function provideReadQueries(): array
    {
        return array(
            'select' => array('SELECT userid FROM users WHERE userid = 1'),
            'select with leading whitespace' => array("\n    select userid FROM users WHERE userid = 1"),
            'select in parenthesis' => array('(SELECT userid FROM users WHERE userid = 1) UNION (SELECT userid FROM users WHERE userid = 1)'),
            'select with a column named like a write' => array('SELECT userid, modified_at AS updated, 0 AS deleted FROM users WHERE userid = 1'),
            'recursive cte' => array('WITH RECURSIVE n AS (SELECT 1 AS i UNION ALL SELECT i + 1 FROM n WHERE i < 3) SELECT i FROM n'),
            'show' => array('SHOW TABLES'),
        );
    }

    #[DataProvider('provideWriteQueries')]
    public function testWriteQueriesAreRefused(string $sql): void
    {
        $this->expectException(ForbiddenException::class);
        $this->Db->prepare($sql);
    }

    #[DataProvider('provideWriteQueries')]
    public function testWriteQueriesAreRefusedWithQ(string $sql): void
    {
        $this->expectException(ForbiddenException::class);
        $this->Db->q($sql);
    }

    public static function provideWriteQueries(): array
    {
        return array(
            'insert' => array('INSERT INTO audit_logs (category, body) VALUES (1, "x")'),
            'update' => array('UPDATE users SET firstname = "pwned" WHERE userid = 1'),
            'delete' => array('DELETE FROM users WHERE userid = 1'),
            'replace' => array('REPLACE INTO config (conf_name, conf_value) VALUES ("x", "y")'),
            'cte followed by a write' => array('WITH t AS (SELECT 1 AS userid) UPDATE users SET firstname = "pwned" WHERE userid IN (SELECT userid FROM t)'),
            'lowercase' => array('update users set firstname = "pwned" where userid = 1'),
            'set' => array('SET @a = 1'),
            'ddl' => array('DROP TABLE users'),
        );
    }

    public function testWritesAreAllowedAgainOnceDisabled(): void
    {
        $this->Db->setReadOnly(false);
        $this->assertFalse($this->Db->isReadOnly());
        // prepare only: we don't want to actually change data
        $this->assertNotNull($this->Db->prepare('UPDATE users SET firstname = firstname WHERE userid = 0'));
    }
}
