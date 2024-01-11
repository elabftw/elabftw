<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;

class OrderingParamsTest extends \PHPUnit\Framework\TestCase
{
    public function testIncorrectJson(): void
    {
        $this->expectException(ImproperActionException::class);
        new OrderingParams('{a');
    }
}
