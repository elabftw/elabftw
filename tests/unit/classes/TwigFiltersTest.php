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
        $metadataJson = '{
          "extra_fields": {
            "url default": {
              "type": "url",
              "value": "https://example.com",
              "position": 6
            },
            "url current tab": {
              "type": "url",
              "value": "https://example.com",
              "open_in_current_tab": true,
              "position": 5
            },
            "last one": {
              "type": "text",
              "value": "last content",
              "position": 42,
              "description": "last position"
            },
            "first one": {
              "type": "text",
              "value": "first",
              "position": 1
            },
            "second one": {
              "type": "text",
              "value": "second",
              "position": 2
            },
            "unchecked checkbox": {
              "type": "checkbox",
              "value": "",
              "position": 4
            },
            "checked checkbox": {
              "type": "checkbox",
              "value": "on"
            }
          }
        }';
        $expected = '<h4 class="m-0">first one</h4><p>first</p><h4 class="m-0">second one</h4><p>second</p><h4 class="m-0">unchecked checkbox</h4><p><input class="d-block" disabled type="checkbox" ></p><h4 class="m-0">url current tab</h4><p><a href="https://example.com" >https://example.com</a></p><h4 class="m-0">url default</h4><p><a href="https://example.com" target="_blank" rel="noopener">https://example.com</a></p><h4 class="m-0">last one</h4><h5>last position</h5><p>last content</p><h4 class="m-0">checked checkbox</h4><p><input class="d-block" disabled type="checkbox" checked></p>';
        $this->assertEquals($expected, TwigFilters::formatMetadata($metadataJson));
    }
}
