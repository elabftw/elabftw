<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Enums\EntityType;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users\Users;

class AccessKeyHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testGetIdFromAccessKey(): void
    {
        $Entity = new Experiments(new Users(1, 1));
        $id = $Entity->create();
        $Entity->setId($id);
        $AkHelper = new AccessKeyHelper(EntityType::Experiments, $id);
        // set an ak
        $ak = $AkHelper->toggleAccessKey();
        $this->assertNotNull($ak);
        $this->assertMatchesRegularExpression('/\A[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/', $ak);
        $this->assertEquals($id, $AkHelper->getIdFromAccessKey($ak));
        // now remove ak
        $AkHelper->toggleAccessKey();
        $this->assertEquals(0, $AkHelper->getIdFromAccessKey($ak));
        // a soft-deleted entity must not be resolved from its old access key
        $ak = $AkHelper->toggleAccessKey();
        $Entity->destroy();
        $this->assertEquals(0, $AkHelper->getIdFromAccessKey($ak));
    }
}
