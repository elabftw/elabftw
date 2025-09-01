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

class DisplayParamsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    public function testParams(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $params = new DisplayParams($user, EntityType::Experiments, new InputBag(array('scope' => 3, 'tags' => array('Yep'), 'cat' => '1,2,null', 'status' => 1, 'related' => 1, 'fastq' => 'a', 'extended' => 'b')));
        $params->setSkipOrderPinned(true);
        $this->assertIsString($params->getSql());
        $this->assertTrue($params->skipOrderPinned);
        $this->assertSame('a', $params->getFastq());
        // another one to get to the owner filter
        $params = new DisplayParams($user, EntityType::Experiments, new InputBag(array('scope' => 1, 'cat' => 'null')), skipOrderPinned: true);
        $this->assertStringContainsString('entity.userid', $params->filterSql);
    }
}
