<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Models\Config;

class TwigFiltersTest extends \PHPUnit\Framework\TestCase
{
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
              "value": "https://example.com/foo/bar.php?fizz=buzz&test=success&amp;test2=elabftw",
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
            "number with unit": {
              "type": "number",
              "value": 12,
              "unit": "kPa"
            },
            "multi select": {
              "type": "select",
              "allow_multi_values": true,
              "value": ["yep", "yip"],
              "options": ["yip", "yap", "yep"]
            },
            "checked checkbox": {
              "type": "checkbox",
              "value": "on"
            },
            "experiments link": {
              "type": "experiments",
              "value": 1,
              "group_id": 1
            }
          },
          "elabftw": {
            "extra_fields_groups": [
              {
                "id": 1,
                "name": "Some <&\'\"> group"
              }
            ]
          }
        }';
        $expected = sprintf(
            '<h4 data-action=\'toggle-next\' class=\'mt-4 d-inline togglable-section-title\'><i class=\'fas fa-caret-down fa-fw mr-2\'></i>Some &lt;&amp;&apos;&quot;&gt; group</h4><div>'
                . '%1$sexperiments link</h5><h6><a href="/experiments.php?mode=view&amp;id=1" target="_blank" rel="noopener"><span data-replace-with-title="true" data-id="1" data-endpoint=experiments>1</span></a></h6></li>'
                . '</div>'
                . '<h4 data-action=\'toggle-next\' class=\'mt-4 d-inline togglable-section-title\'><i class=\'fas fa-caret-down fa-fw mr-2\'></i>Undefined group</h4><div>'
                . '%1$sfirst one</h5><h6>first</h6></li>'
                . '%1$ssecond one</h5><h6>second</h6></li>'
                . '%1$sunchecked checkbox</h5><h6><input class="d-block" disabled type="checkbox"></h6></li>'
                . '%1$surl current tab</h5><h6>'
                . '<a href="https://example.com/foo/bar.php?fizz=buzz&amp;test=success&amp;test2=elabftw">https://example.com/foo/bar.php?fizz=buzz&amp;test=success&amp;test2=elabftw</a></h6></li>'
                . '%1$surl default</h5><h6><a href="https://example.com" target="_blank" rel="noopener">https://example.com</a></h6></li>'
                . '%1$slast one</h5><span class="smallgray">last position</span><h6>last content</h6></li>'
                . '%1$snumber with unit</h5><h6>12 kPa</h6></li>'
                . '%1$smulti select</h5><h6><p>yep</p><p>yip</p></h6></li>'
                . '%1$schecked checkbox</h5><h6><input class="d-block" disabled type="checkbox" checked="checked"></h6></li>'
                . '</div>',
            '<li class="list-group-item"><h5 class="mb-0">',
        );
        $this->assertEquals($expected, TwigFilters::formatMetadata($metadataJson));
    }

    public function testFormatMetadataEmptyExtrafields(): void
    {
        $metadata = '{"hello": "friend"}';
        $this->assertIsString(TwigFilters::formatMetadata($metadata));
    }

    public function testDecrypt(): void
    {
        $secret = 'Section 31';
        $key = Key::loadFromAsciiSafeString(Config::fromEnv('SECRET_KEY'));
        $encrypted = Crypto::encrypt($secret, $key);
        $this->assertEquals($secret, TwigFilters::decrypt($encrypted));
        $this->assertEmpty(TwigFilters::decrypt(null));
    }
}
