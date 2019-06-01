<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

class TagCloudTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->TagCloud = new TagCloud(1);
    }

    public function testReadAll()
    {
        $this->assertTrue(is_array($this->TagCloud->getCloudArr()));
    }
}
