<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

class TagCloudTest extends \PHPUnit\Framework\TestCase
{
    private TagCloud $TagCloud;

    protected function setUp(): void
    {
        $this->TagCloud = new TagCloud(1);
    }

    public function testReadAll(): void
    {
        $this->assertTrue(is_array($this->TagCloud->getCloudArr()));
    }
}
