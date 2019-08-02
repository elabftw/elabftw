<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Exceptions\ImproperActionException;

class CheckerTest extends \PHPUnit\Framework\TestCase
{
    public function testCheckPasswordLength()
    {
        $this->assertTrue(Checker::checkPasswordLength('longpassword'));
        $this->expectException(ImproperActionException::class);
        Checker::checkPasswordLength('short');
    }
}
