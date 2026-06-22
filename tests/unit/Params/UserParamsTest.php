<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Params;

use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\Entrypoint;
use Elabftw\Enums\SchedulerLayout;
use Elabftw\Exceptions\ImproperActionException;

class UserParamsTest extends \PHPUnit\Framework\TestCase
{
    public function testValidUntilEmpty(): void
    {
        $params = new UserParams('valid_until', '');
        $this->assertEquals('3000-01-01', $params->getContent());
    }

    public function testValidUntil(): void
    {
        $input = '2023-02-03';
        $params = new UserParams('valid_until', $input);
        $this->assertEquals($input, $params->getContent());
    }

    public function testOrcid(): void
    {
        $orcid = '1234-5678-1212-0001';
        $params = new UserParams('orcid', $orcid);
        $this->assertEquals($orcid, $params->getContent());
    }

    public function testInvalidOrcidFormat(): void
    {
        $orcid = '1234-5678-1212-001';
        $params = new UserParams('orcid', $orcid);
        $this->expectException(ImproperActionException::class);
        $params->getContent();
    }

    public function testInvalidOrcidChecksum(): void
    {
        $orcid = '1234-5678-1212-000X';
        $params = new UserParams('orcid', $orcid);
        $this->expectException(ImproperActionException::class);
        $params->getContent();
    }

    public function testEntryPoint(): void
    {
        $entrypoint = Entrypoint::Experiments->value;
        $params = new UserParams('entrypoint', $entrypoint);
        $this->assertEquals($entrypoint, $params->getContent());
    }

    public function testInvalidEntryPointDefaultsToDashboard(): void
    {
        $entrypoint = 'test';
        $params = new UserParams('entrypoint', $entrypoint);
        $this->assertEquals(Entrypoint::Dashboard->value, $params->getContent());
    }

    public function testSchedulerLayout(): void
    {
        $params = new UserParams('scheduler_layout', '1');
        $this->assertIsInt($params->getContent());
    }

    public function testInvalidSchedulerLayoutDefaultsToAuto(): void
    {
        $params = new UserParams('scheduler_layout', '999');
        $this->assertEquals(SchedulerLayout::Classic->value, $params->getContent());
    }

    public function testDefaultReadBase(): void
    {
        $params = new UserParams('default_read_base', '10');
        $this->assertEquals(BasePermissions::UserOnly->value, $params->getContent());
    }

    public function testDefaultWriteBase(): void
    {
        $params = new UserParams('default_write_base', '10');
        $this->assertEquals(BasePermissions::UserOnly->value, $params->getContent());
    }
}
