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

use Elabftw\Enums\EntityType;
use Elabftw\Traits\TestsUtilsTrait;
use Symfony\Component\HttpFoundation\InputBag;

use function array_map;
use function range;
use function str_repeat;

class DisplayParamsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    public function testParams(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $params = new DisplayParams($user, EntityType::Experiments, new InputBag(array('scope' => 3, 'tags' => array('Yep'), 'cat' => '1,2,null', 'status' => 1, 'related' => 1, 'fastq' => 'a', 'extended' => 'b', 'full' => '1')));
        $params->setSkipOrderPinned(true);
        $this->assertIsString($params->getSql());
        $this->assertTrue($params->skipOrderPinned);
        $this->assertSame('a', $params->getFastq());
        $this->assertEquals(0, $params->getLimit());
        $this->assertTrue($params->isFull());
        // another one to get to the owner filter
        $params = new DisplayParams($user, EntityType::Experiments, new InputBag(array('scope' => 1, 'cat' => 'null')), skipOrderPinned: true);
        $this->assertStringContainsString('entity.userid', $params->filterSql);
        // compound filter uses the link table of the entity type
        $params = new DisplayParams($user, EntityType::Items, new InputBag(array('compound' => 3)));
        $this->assertStringContainsString('compounds2items AS c2e_filter WHERE c2e_filter.compound_id = 3', $params->filterSql);
        // invalid compound id is ignored
        $params = new DisplayParams($user, EntityType::Items, new InputBag(array('compound' => 'nope')));
        $this->assertStringNotContainsString('compounds2items', $params->filterSql);
    }

    public function testExtraFieldsColumns(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $params = new DisplayParams($user, EntityType::Experiments);
        $this->assertSame(array(), $params->getExtraFieldsColumns());
        // duplicates, empty names, too long names and non string values are ignored
        $params = new DisplayParams($user, EntityType::Experiments, new InputBag(array('extra_fields' => array(
            'Concentration',
            'Concentration',
            '',
            '  ',
            str_repeat('a', DisplayParams::MAX_EXTRA_FIELD_NAME_LENGTH + 1),
            array('nested'),
            'solvent "quoted"',
        ))));
        $this->assertSame(array('Concentration', 'solvent "quoted"'), $params->getExtraFieldsColumns());
        // a scalar value doesn't make it throw
        $params = new DisplayParams($user, EntityType::Experiments, new InputBag(array('extra_fields' => 'Solvent')));
        $this->assertSame(array('Solvent'), $params->getExtraFieldsColumns());
        // the number of columns is capped
        $names = array_map(static fn(int $i): string => 'field ' . $i, range(1, DisplayParams::MAX_EXTRA_FIELDS_COLUMNS + 5));
        $params = new DisplayParams($user, EntityType::Experiments, new InputBag(array('extra_fields' => $names)));
        $this->assertCount(DisplayParams::MAX_EXTRA_FIELDS_COLUMNS, $params->getExtraFieldsColumns());
    }
}
