<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\AuditEvent\UserLogin;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Models\Users\Users;
use Elabftw\Params\BaseQueryParams;
use Symfony\Component\HttpFoundation\InputBag;

use function count;

class AuditLogsTest extends \PHPUnit\Framework\TestCase
{
    private AuditLogs $AuditLogs;

    protected function setUp(): void
    {
        $this->AuditLogs = new AuditLogs(new Users(1, 1));
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/audit_logs/', $this->AuditLogs->getApiPath());
    }

    public function testReadAll(): void
    {
        AuditLogs::create(new UserLogin(1, 1));
        AuditLogs::create(new UserLogin(1, 1));
        $all = $this->AuditLogs->readAll();
        $this->assertGreaterThanOrEqual(2, count($all));
        $this->assertLessThanOrEqual(AuditLogs::MAX_LIMIT, count($all));
        $this->assertArrayHasKey('category_name', $all[0]);
        $this->assertArrayHasKey('requester_fullname', $all[0]);
        $this->assertArrayHasKey('target_fullname', $all[0]);
        // most recent first
        $this->assertGreaterThan($all[1]['id'], $all[0]['id']);

        $limited = $this->AuditLogs->readAll(new BaseQueryParams(new InputBag(array('limit' => 1))));
        $this->assertCount(1, $limited);
        $this->assertEquals($all[0]['id'], $limited[0]['id']);
    }

    public function testReadAllNotSysadmin(): void
    {
        $AuditLogs = new AuditLogs(new Users(2, 1));
        $this->expectException(ForbiddenException::class);
        $AuditLogs->readAll();
    }
}
