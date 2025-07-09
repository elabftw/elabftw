<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\PermissionsHelper;
use Elabftw\Enums\BasePermissions;

class PermissionsHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testGetAssociativeArray(): void
    {
        $permissionHelper = new PermissionsHelper();
        $permissions = $permissionHelper->getAssociativeArray();
        $this->assertArrayHasKey(BasePermissions::Team->value, $permissions);
        $this->assertArrayHasKey(BasePermissions::Full->value, $permissions);
        $this->assertArrayHasKey(BasePermissions::Organization->value, $permissions);
    }
}
