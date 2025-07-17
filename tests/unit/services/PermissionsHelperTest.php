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
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Models\Config;

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

    public function testGetExtendedSearchAssociativeArrayWithUserOnlyDisabled(): void
    {
        $Config = Config::getConfig();
        $Config->patch(Action::Update, array('allow_permission_useronly' => '0'));
        $permissionHelper = new PermissionsHelper();
        $extendedSearch = $permissionHelper->getExtendedSearchAssociativeArray();
        $this->assertArrayHasKey('public', $extendedSearch);
        $this->assertArrayNotHasKey('useronly', $extendedSearch);
    }
}
