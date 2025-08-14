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

use Elabftw\Exceptions\ImproperActionException;

class ContentParamsTest extends \PHPUnit\Framework\TestCase
{
    public function testTooshort(): void
    {
        $params = new ContentParams('comment', '');
        $this->expectException(ImproperActionException::class);
        $params->getContent();
    }

    public function testNullableString(): void
    {
        $params = new StepParams('deadline', '');
        $this->assertNull($params->getContent());
    }

    public function testGetEnum(): void
    {
        $params = new ProcurementRequestParams('state', 'aaa');
        $this->expectException(ImproperActionException::class);
        $params->getContent();
    }
}
