<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

class DatabaseCleanerTest extends \PHPUnit\Framework\TestCase
{
    public function testCleanup()
    {
        $DatabaseCleaner = new DatabaseCleaner();
        $DatabaseCleaner->cleanup();
    }
}
