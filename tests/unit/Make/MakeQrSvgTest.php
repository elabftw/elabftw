<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

use Elabftw\Models\Experiments;
use ReflectionClass;
use SimpleXMLElement;

use function strlen;

class MakeQrSvgTest extends \PHPUnit\Framework\TestCase
{
    private Experiments $Entity;

    protected function setUp(): void
    {
        // The maker only reads these fields; no database-backed entity is needed.
        $this->Entity = new ReflectionClass(Experiments::class)->newInstanceWithoutConstructor();
        $this->Entity->entityData = array(
            'title' => 'PCR amplification',
            'sharelink' => 'https://elab.example/experiments.php?mode=view&id=42',
        );
    }

    public function testGetFileContent(): void
    {
        $Maker = new MakeQrSvg($this->Entity, 250);
        $content = $Maker->getFileContent();
        $svg = new SimpleXMLElement($content);
        $this->assertSame(array('' => 'http://www.w3.org/2000/svg'), $svg->getDocNamespaces());
        $this->assertSame('250', (string) $svg['width']);
        $this->assertSame('270', (string) $svg['height']);
        $this->assertSame('0 0 250 270', (string) $svg['viewBox']);
        $this->assertGreaterThan(1, $svg->rect->count());
        $this->assertSame(0, $svg->image->count());
        $this->assertSame('PCR amplification', (string) $svg->text[0]);
        $this->assertGreaterThan(250, (int) $svg->text[0]['y']);
        $this->assertSame(strlen($content), $Maker->getContentSize());
        $this->assertSame('image/svg+xml', $Maker->getContentType());
        $this->assertStringEndsWith('qr-code.elabftw.svg', $Maker->getFileName());
    }

    public function testGetFileContentWithoutTitle(): void
    {
        $svg = new SimpleXMLElement(new MakeQrSvg($this->Entity, 50, false)->getFileContent());
        $this->assertSame('0 0 50 50', (string) $svg['viewBox']);
        $this->assertSame(0, $svg->text->count());
    }

    public function testTitleIsXmlEscaped(): void
    {
        $title = 'PCR & <script>alert("x")</script> 日本語 العربية';
        $this->Entity->entityData['title'] = $title;
        $svg = new SimpleXMLElement(new MakeQrSvg($this->Entity, 250, maxLineChars: 100)->getFileContent());
        $this->assertSame($title, (string) $svg->text[0]);
        $this->assertSame(array(), $svg->xpath('//*[local-name()="script"]'));
    }

    public function testTitleWrappingAndLineLimit(): void
    {
        $this->Entity->entityData['title'] = '日本語ABCDEF';
        $svg = new SimpleXMLElement(new MakeQrSvg($this->Entity, 0, maxLines: 2, maxLineChars: 3)->getFileContent());
        $this->assertSame('0 0 250 290', (string) $svg['viewBox']);
        $this->assertSame(2, $svg->text->count());
        $this->assertSame('日本語', (string) $svg->text[0]);
        $this->assertSame('ABC', (string) $svg->text[1]);
        $this->assertSame('265', (string) $svg->text[0]['y']);
        $this->assertSame('285', (string) $svg->text[1]['y']);
    }

    public function testWideTitleFitsTheCanvas(): void
    {
        $this->Entity->entityData['title'] = '日本語WWWW';
        $svg = new SimpleXMLElement(new MakeQrSvg($this->Entity, 50)->getFileContent());
        $this->assertSame('90', (string) $svg['width']);
        $this->assertSame('80', (string) $svg->text[0]['textLength']);
        $this->assertSame('spacingAndGlyphs', (string) $svg->text[0]['lengthAdjust']);
        $this->assertSame('90', (string) $svg->rect[0]['width']);
        $this->assertSame('70', (string) $svg->rect[0]['height']);
    }

    public function testEmptyTitle(): void
    {
        $this->Entity->entityData['title'] = '';
        $svg = new SimpleXMLElement(new MakeQrSvg($this->Entity, 250)->getFileContent());
        $this->assertSame('0 0 250 250', (string) $svg['viewBox']);
        $this->assertSame(0, $svg->text->count());
    }
}
