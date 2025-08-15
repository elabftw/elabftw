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
use Elabftw\Enums\MessageLevels;

class TwigFiltersTest extends \PHPUnit\Framework\TestCase
{
    public function testDisplayMessage(): void
    {
        $this->assertIsString(TwigFilters::displayMessage('ok', MessageLevels::Ok, true));
        $this->assertIsString(TwigFilters::displayMessage('ok', 'ko'));
    }

    public function testToIcon(): void
    {
        $this->assertIsString(TwigFilters::toIcon(1));
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
            "email": {
              "type": "email",
              "value": "email@example.fr"
            },
            "invalid users": {
              "type": "users",
              "value": 99999999
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
        // just copy/paste the expected block of a failing test here. Then run s/'/\\'/g to escape single quotes (except first and last of course)
        $expected = '<div><h4 data-action=\'toggle-next\' data-opened-icon=\'fa-caret-down\' data-closed-icon=\'fa-caret-right\' class=\'mt-4 d-inline togglable-section-title\'><i class=\'fas fa-caret-down fa-fw mr-2\'></i>Some &lt;&amp;&apos;&quot;&gt; group</h4><ul class="list-group"><li class="list-group-item"><h5 class="mb-0">experiments link</h5><h6><a href="/experiments.php?mode=view&amp;id=1" target="_blank" rel="noopener"><span data-replace-with-title="true" data-id="1" data-endpoint=experiments>1</span></a></h6></li></ul></div><div><h4 data-action=\'toggle-next\' data-opened-icon=\'fa-caret-down\' data-closed-icon=\'fa-caret-right\' class=\'mt-4 d-inline togglable-section-title\'><i class=\'fas fa-caret-down fa-fw mr-2\'></i>Undefined group</h4><ul class="list-group"><li class="list-group-item"><h5 class="mb-0">first one</h5><h6>first</h6></li><li class="list-group-item"><h5 class="mb-0">second one</h5><h6>second</h6></li><li class="list-group-item"><h5 class="mb-0">unchecked checkbox</h5><h6><input class="d-block" disabled type="checkbox"></h6></li><li class="list-group-item"><h5 class="mb-0">url current tab</h5><h6><a href="https://example.com/foo/bar.php?fizz=buzz&amp;test=success&amp;test2=elabftw">https://example.com/foo/bar.php?fizz=buzz&amp;test=success&amp;test2=elabftw</a></h6></li><li class="list-group-item"><h5 class="mb-0">url default</h5><h6><a href="https://example.com" target="_blank" rel="noopener">https://example.com</a></h6></li><li class="list-group-item"><h5 class="mb-0">last one</h5><span class="smallgray">last position</span><h6>last content</h6></li><li class="list-group-item"><h5 class="mb-0">email</h5><h6><a href="mailto:email@example.fr">email@example.fr</a></h6></li><li class="list-group-item"><h5 class="mb-0">invalid users</h5><h6>User could not be found.</h6></li><li class="list-group-item"><h5 class="mb-0">number with unit</h5><h6>12 kPa</h6></li><li class="list-group-item"><h5 class="mb-0">multi select</h5><h6><p>yep</p><p>yip</p></h6></li><li class="list-group-item"><h5 class="mb-0">checked checkbox</h5><h6><input class="d-block" disabled type="checkbox" checked="checked"></h6></li></ul></div>';

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
        $key = Key::loadFromAsciiSafeString(Env::asString('SECRET_KEY'));
        $encrypted = Crypto::encrypt($secret, $key);
        $this->assertEquals($secret, TwigFilters::decrypt($encrypted));
        $this->assertEmpty(TwigFilters::decrypt(null));
    }

    public function testToSymbol(): void
    {
        $this->assertIsString(TwigFilters::toSymbol(7));
    }

    public function testJsonDecode(): void
    {
        $json = '[]';
        $this->assertEquals(array(), TwigFilters::jsonDecode($json));
    }
}
