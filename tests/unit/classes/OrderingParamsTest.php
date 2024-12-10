<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Enums\EntityType;
use Elabftw\Enums\Orderable;

class OrderingParamsTest extends \PHPUnit\Framework\TestCase
{
    public function testIncorrectJson(): void
    {
        $OrderingParams = new OrderingParams(array('ordering' => array('test_1', 'test_2', 'test_3'), 'table' => 'items_types_steps'));
        $this->assertInstanceOf(Orderable::class, $OrderingParams->table);
        $this->assertIsArray($OrderingParams->ordering);
    }

    public function testExtraFields(): void
    {
        $OrderingParams = new ExtraFieldsOrderingParams(array(
            'entity' => array('type' => EntityType::Experiments->value, 'id' => '123'),
            'ordering' => array('test_1', 'test_2', 'test_3'),
            'table' => 'extra_fields',
        ));
        $this->assertInstanceOf(EntityType::class, $OrderingParams->entityType);
        $this->assertEquals(123, $OrderingParams->id);
    }
}
