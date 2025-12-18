<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Params;

use Symfony\Component\HttpFoundation\InputBag;

class BaseQueryParamsTest extends \PHPUnit\Framework\TestCase
{
    public function testOffset(): void
    {
        $params = new BaseQueryParams(new InputBag(array('offset' => 1)));
        $this->assertSame(1, $params->offset);
        $this->assertFalse($params->isFast());
        $this->assertEquals(0, $params->getLimit());
        $this->assertEmpty($params->getFastq());
        $this->assertFalse($params->hasUserQuery());
        $this->assertEmpty($params->getUserQuery());
        $this->assertNull($params->getRelatedOrigin());
        $this->assertEmpty($params->getFilterSql());
        $params->setSkipOrderPinned(true);
    }
}
