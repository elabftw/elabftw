<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha Camara <mouss@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\PermissionsHelper;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Models\Config;

class PermissionsHelperTest extends \PHPUnit\Framework\TestCase
{
    private PermissionsHelper $PermissionsHelper;

    private Config $Config;

    private array $setupValues;

    protected function setUp(): void
    {
        $this->PermissionsHelper = new PermissionsHelper();
        $this->Config = Config::getConfig();
        $this->setupValues = $this->Config->configArr;
    }

    protected function tearDown(): void
    {
        $this->Config->configArr = $this->setupValues;
    }

    public function testGetAssociativeArrayWithValidPermissions(): void
    {
        $permissions = $this->PermissionsHelper->getAssociativeArray();
        $this->assertArrayHasKey(BasePermissions::Team->value, $permissions);
        $this->assertArrayHasKey(BasePermissions::Full->value, $permissions);

        // Must have at least one permission
        $this->Config->patch(Action::Update, array(
            'allow_permission_team' => '0',
            'allow_permission_user' => '0',
            'allow_permission_full' => '0',
            'allow_permission_organization' => '0',
            'allow_permission_useronly' => '0',
        ));
        $this->expectException(IllegalActionException::class);
        $this->PermissionsHelper->getAssociativeArray();
    }
}
