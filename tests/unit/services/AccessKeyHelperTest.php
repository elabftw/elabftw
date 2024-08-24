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

use Elabftw\Models\Experiments;
use Elabftw\Models\Users;

class AccessKeyHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testGetIdFromAccessKey(): void
    {
        $Entity = new Experiments(new Users(1, 1));
        $id = $Entity->create();
        $Entity->setId($id);
        $AkHelper = new AccessKeyHelper($Entity);
        // set an ak
        $ak = $AkHelper->toggleAccessKey();
        $this->assertEquals($id, $AkHelper->getIdFromAccessKey($ak));
        // now remove ak
        $AkHelper->toggleAccessKey();
        $this->assertEquals(0, $AkHelper->getIdFromAccessKey($ak));
    }
}
