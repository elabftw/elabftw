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

use Elabftw\Enums\MessageLevels;

use function sprintf;

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
            "Compound field type": {
              "type": "compounds",
              "value": 1,
              "description": "This field creates a link to the compound."
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
        $expected = '<div><h4 data-action=\'toggle-next\' data-opened-icon=\'fa-caret-down\' data-closed-icon=\'fa-caret-right\' class=\'mt-4 d-inline togglable-section-title\'><i class=\'fas fa-caret-down fa-fw mr-2\'></i>Some &lt;&amp;&apos;&quot;&gt; group</h4><ul class="list-group"><li class="list-group-item"><h5 class="mb-0">experiments link</h5><h6><a href="/experiments.php?mode=view&amp;id=1" target="_blank" rel="noopener"><span data-replace-with-title="true" data-id="1" data-endpoint="experiments">1</span></a></h6></li></ul></div><div><h4 data-action=\'toggle-next\' data-opened-icon=\'fa-caret-down\' data-closed-icon=\'fa-caret-right\' class=\'mt-4 d-inline togglable-section-title\'><i class=\'fas fa-caret-down fa-fw mr-2\'></i>Undefined group</h4><ul class="list-group"><li class="list-group-item"><h5 class="mb-0">first one</h5><h6>first</h6></li><li class="list-group-item"><h5 class="mb-0">second one</h5><h6>second</h6></li><li class="list-group-item"><h5 class="mb-0">unchecked checkbox</h5><h6><input class="d-block" disabled type="checkbox"></h6></li><li class="list-group-item"><h5 class="mb-0">url current tab</h5><h6><a href="https://example.com/foo/bar.php?fizz=buzz&amp;test=success&amp;test2=elabftw">https://example.com/foo/bar.php?fizz=buzz&amp;test=success&amp;test2=elabftw</a></h6></li><li class="list-group-item"><h5 class="mb-0">url default</h5><h6><a href="https://example.com" target="_blank" rel="noopener">https://example.com</a></h6></li><li class="list-group-item"><h5 class="mb-0">last one</h5><span class="smallgray">last position</span><h6>last content</h6></li><li class="list-group-item"><h5 class="mb-0">email</h5><h6><a href="mailto:email@example.fr">email@example.fr</a></h6></li><li class="list-group-item"><h5 class="mb-0">invalid users</h5><h6>User could not be found.</h6></li><li class="list-group-item"><h5 class="mb-0">number with unit</h5><h6>12 kPa</h6></li><li class="list-group-item"><h5 class="mb-0">multi select</h5><h6><p>yep</p><p>yip</p></h6></li><li class="list-group-item"><h5 class="mb-0">checked checkbox</h5><h6><input class="d-block" disabled type="checkbox" checked="checked"></h6></li><li class="list-group-item"><h5 class="mb-0">Compound field type</h5><span class="smallgray">This field creates a link to the compound.</span><h6><span data-replace-with-title="true" data-id="1" data-endpoint="compounds">1</span></h6></li></ul></div>';

        $this->assertEquals($expected, TwigFilters::formatMetadata($metadataJson));
    }

    public function testFormatMetadataWithMultiValueLabels(): void
    {
        $metadataJson = '{
          "extra_fields": {
            "checks": {
              "type": "checkbox",
              "allow_multi_values": true,
              "value": ["on", "off"],
              "value_labels": ["Calibrated", "Reviewed <&\\" done"]
            }
          }
        }';

        $result = TwigFilters::formatMetadata($metadataJson);

        $this->assertStringContainsString('<p><span class="badge badge-pill badge-light">Calibrated</span><br><input class="d-block" disabled type="checkbox" checked="checked"></p>', $result);
        $this->assertStringContainsString('<p><span class="badge badge-pill badge-light">Reviewed &lt;&amp;&quot; done</span><br><input class="d-block" disabled type="checkbox"></p>', $result);
    }

    public function testFormatMetadataWithPartialValueLabels(): void
    {
        $metadataJson = '{
          "extra_fields": {
            "numbers": {
              "type": "number",
              "value": ["1", "2", "3"],
              "unit": "mg",
              "value_labels": ["0", "", "<img src=x onerror=alert(1)>"]
            },
            "short labels": {"value": ["A", "B"], "value_labels": ["first"]},
            "no labels": {"value": ["C"], "value_labels": null}
          }
        }';

        $result = TwigFilters::formatMetadata($metadataJson);

        $this->assertStringContainsString('">0</span><br>1 mg</p><p>2 mg</p>', $result);
        $this->assertStringContainsString('">&lt;img src=x onerror=alert(1)&gt;</span><br>3 mg</p>', $result);
        $this->assertStringContainsString('">first</span><br>A</p><p>B</p>', $result);
        $this->assertStringContainsString('<p>C</p>', $result);
        $this->assertStringNotContainsString('<img', $result);
    }

    public function testFormatMetadataWithMultipleValuesForAllTypes(): void
    {
        $metadataJson = '{
          "extra_fields": {
            "multi text": {
              "type": "text",
              "allow_multi_values": true,
              "value": ["first", "second"]
            },
            "multi url": {
              "type": "url",
              "allow_multi_values": true,
              "value": ["https://example.com/one", "https://example.com/two"]
            },
            "multi number": {
              "type": "number",
              "allow_multi_values": true,
              "value": ["1", "2"],
              "unit": "mg"
            },
            "multi checkbox": {
              "type": "checkbox",
              "allow_multi_values": true,
              "value": ["on", "off"]
            },
            "multi compounds": {
              "type": "compounds",
              "allow_multi_values": true,
              "value": [12, 34]
            }
          }
        }';

        $result = TwigFilters::formatMetadata($metadataJson);

        $this->assertStringContainsString('<p>first</p><p>second</p>', $result);
        $this->assertStringContainsString('<p><a href="https://example.com/one" target="_blank" rel="noopener">https://example.com/one</a></p>', $result);
        $this->assertStringContainsString('<p>1 mg</p><p>2 mg</p>', $result);
        $this->assertStringContainsString('<p><input class="d-block" disabled type="checkbox" checked="checked"></p>', $result);
        $this->assertStringContainsString('<p><input class="d-block" disabled type="checkbox"></p>', $result);
        $this->assertStringContainsString('<p><span data-replace-with-title="true" data-id="12" data-endpoint="compounds">12</span></p>', $result);
        $this->assertStringContainsString('<p><span data-replace-with-title="true" data-id="34" data-endpoint="compounds">34</span></p>', $result);
    }

    public function testFormatMetadataWithLabel(): void
    {
        $metadataJson = '{
          "extra_fields": {
            "Rotation": {
              "type": "number",
              "value": "10.0",
              "unit": "rpm",
              "label": {"text": "Unverified", "color": "e6614c", "title": "written by the LIMS"}
            }
          }
        }';

        $result = TwigFilters::formatMetadata($metadataJson);

        // the flat background is the color mixed at 40% over white for mpdf, the
        // custom property is what main.scss mixes with color-mix() in the browser
        $this->assertStringContainsString(
            '<div class="d-flex align-items-start"><div><h5 class="mb-0">Rotation</h5><h6>10.0 rpm</h6></div><div class="extra-field-label-wrapper"><span class="extra-field-label" style="background-color: #f5c0b7; --label-bg: #e6614c" title="written by the LIMS">Unverified</span></div></div>',
            $result,
        );
    }

    public function testFormatMetadataLabelIsEscaped(): void
    {
        $metadataJson = '{
          "extra_fields": {
            "Rotation": {
              "type": "number",
              "value": "10.0",
              "label": {"text": "<b>Unverified</b>", "color": "e6614c", "title": "she said \"hi\" & <bye>"}
            }
          }
        }';

        $result = TwigFilters::formatMetadata($metadataJson);

        $this->assertStringContainsString('title="she said &quot;hi&quot; &amp; &lt;bye&gt;"', $result);
        $this->assertStringContainsString('>&lt;b&gt;Unverified&lt;/b&gt;</span>', $result);
        $this->assertStringNotContainsString('<b>', $result);
    }

    public function testFormatMetadataLabelWithInvalidColor(): void
    {
        // an unusable color must degrade to the neutral grey instead of throwing:
        // a view must not fail over a presentation detail
        foreach (array('"not a color"', '"#12345"', '""', '42', 'null') as $color) {
            $metadataJson = sprintf(
                '{"extra_fields": {"Rotation": {"type": "number", "value": "10.0", "label": {"text": "Unverified", "color": %s}}}}',
                $color,
            );

            $result = TwigFilters::formatMetadata($metadataJson);

            $this->assertStringContainsString('style="background-color: #e5e5e5; --label-bg: #bdbdbd"', $result);
            $this->assertStringContainsString('>Unverified</span>', $result);
        }
    }

    public function testFormatMetadataWithoutLabelIsNotWrapped(): void
    {
        // a field that carries no usable label must render exactly as it did
        // before the label feature existed: no wrapper, no flex container
        foreach (array('', ', "label": {"text": ""}', ', "label": "Unverified"', ', "label": {"color": "e6614c"}') as $label) {
            $metadataJson = sprintf(
                '{"extra_fields": {"Rotation": {"type": "number", "value": "10.0", "unit": "rpm"%s}}}',
                $label,
            );

            $result = TwigFilters::formatMetadata($metadataJson);

            $this->assertStringContainsString('<li class="list-group-item"><h5 class="mb-0">Rotation</h5><h6>10.0 rpm</h6></li>', $result);
            $this->assertStringNotContainsString('extra-field-label', $result);
            $this->assertStringNotContainsString('d-flex align-items-start', $result);
        }
    }

    public function testFormatMetadataFailed(): void
    {
        $metadataJsonFailed = '{
        "extra_fields": {
          "XXXXXXXXXXX": {
            "type": "text",
            "value": "",
            "group_id": 1,
            "position": 4,
            "required": true
          },
          "YYYYYYYYY": "",
          "XXXXXXXXXX": {
           "type": "date",
           "value": "",
           "group_id": 1,
           "position": 3,
           "required": true
          }
         }
        }';
        $result = TwigFilters::formatMetadata($metadataJsonFailed);
        $this->assertIsString($result);
        $this->assertStringContainsString('Invalid custom field', $result);
    }

    public function testFormatMetadataEmptyExtrafields(): void
    {
        $metadata = '{"hello": "friend"}';
        $this->assertIsString(TwigFilters::formatMetadata($metadata));
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

    public function testAnyToString(): void
    {
        $this->assertSame('1', TwigFilters::any2string('1'));
        $this->assertSame('', TwigFilters::any2string(null));
    }

    public function testFormatMfaSecret(): void
    {
        $formatted = '44HN HIFE CEJC IBZO V4TR JZGM XVYM OYG6';
        $this->assertSame($formatted, TwigFilters::formatMfaSecret('44HNHIFECEJCIBZOV4TRJZGMXVYMOYG6'));
    }
}
