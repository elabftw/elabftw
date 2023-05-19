<?php declare(strict_types=1);
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
        $expected = '<h4 data-action=\'toggle-next\' class=\'mt-4 d-inline togglable-section-title\'><i class=\'fas fa-caret-down fa-fw mr-2\'></i>Undefined group</h4><div><li class="list-group-item"><h5 class="mb-0">first one</h5><h6>first</h6></li><li class="list-group-item"><h5 class="mb-0">second one</h5><h6>second</h6></li><li class="list-group-item"><h5 class="mb-0">unchecked checkbox</h5><h6><input class="d-block" disabled type="checkbox" ></h6></li><li class="list-group-item"><h5 class="mb-0">url current tab</h5><h6><a href="https://example.com" >https://example.com</a></h6></li><li class="list-group-item"><h5 class="mb-0">url default</h5><h6><a href="https://example.com" target="_blank" rel="noopener">https://example.com</a></h6></li><li class="list-group-item"><h5 class="mb-0">last one</h5><span class="smallgray">last position</span><h6>last content</h6></li><li class="list-group-item"><h5 class="mb-0">checked checkbox</h5><h6><input class="d-block" disabled type="checkbox" checked></h6></li></div>';
        $this->assertEquals($expected, TwigFilters::formatMetadata($metadataJson));
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
