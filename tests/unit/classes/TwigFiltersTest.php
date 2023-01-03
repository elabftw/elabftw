<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

class TwigFiltersTest extends \PHPUnit\Framework\TestCase
{
    public function testShowStar(): void
    {
        $out = "<i style='color:#54aa08' class='fas fa-star' title='☻'></i><i style='color:#54aa08' class='fas fa-star' title='☻'></i><i style='color:gray' class='fas fa-star' title='☺'></i><i style='color:gray' class='fas fa-star' title='☺'></i><i style='color:gray' class='fas fa-star' title='☺'></i>";
        $this->assertEquals($out, TwigFilters::showStars(2));
    }

    public function testFormatMetadata(): void
    {
        $metadataJson = '{"extra_fields":{"foo":{"type":"text","value":"bar","description":"buzz"}}}';
        $expected = '<h4>foo</h4><h5>buzz</h5><p>bar</p>';
        $this->assertEquals($expected, TwigFilters::formatMetadata($metadataJson));
    }
}
