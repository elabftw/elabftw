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
        $this->assertSame('292', (string) $svg['width']);
        $this->assertSame('270', (string) $svg['height']);
        $this->assertSame('0 0 292 270', (string) $svg['viewBox']);
        $this->assertGreaterThan(1, $svg->rect->count());
        $this->assertSame(0, $svg->image->count());
        $this->assertSame('PCR amplification', (string) $svg->text[0]);
        $this->assertSame('16', (string) $svg->text[0]['font-size']);
        $this->assertGreaterThan(250, (int) $svg->text[0]['y']);
        $this->assertSame(strlen($content), $Maker->getContentSize());
        $this->assertSame('image/svg+xml', $Maker->getContentType());
        $this->assertStringEndsWith('qr-code.elabftw.svg', $Maker->getFileName());
    }

    public function testGetFileContentWithoutTitle(): void
    {
        $svg = new SimpleXMLElement(new MakeQrSvg($this->Entity, 50, false, fontSize: 64)->getFileContent());
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
        $this->assertSame('266', (string) $svg->text[0]['y']);
        $this->assertSame('286', (string) $svg->text[1]['y']);
    }

    public function testWideTitleFitsTheCanvas(): void
    {
        $this->Entity->entityData['title'] = '日本語WWWW';
        $svg = new SimpleXMLElement(new MakeQrSvg($this->Entity, 50)->getFileContent());
        $this->assertSame('170', (string) $svg['width']);
        $this->assertFalse(isset($svg->text[0]['textLength']));
        $this->assertFalse(isset($svg->text[0]['lengthAdjust']));
        $this->assertSame('170', (string) $svg->rect[0]['width']);
        $this->assertSame('70', (string) $svg->rect[0]['height']);
    }

    public function testWideLatinTitleIsNotCompressed(): void
    {
        $this->Entity->entityData['title'] = 'WWWW';
        $svg = new SimpleXMLElement(new MakeQrSvg($this->Entity, 10)->getFileContent());
        $this->assertSame('74', (string) $svg['width']);
        $this->assertSame('16', (string) $svg->text[0]['font-size']);
        $this->assertSame('WWWW', (string) $svg->text[0]);
        $this->assertFalse(isset($svg->text[0]['textLength']));
        $this->assertFalse(isset($svg->text[0]['lengthAdjust']));
    }

    public function testCustomFontSizeAdjustsTheTitleLayout(): void
    {
        $this->Entity->entityData['title'] = 'WWWWWWWW';
        $svg = new SimpleXMLElement(new MakeQrSvg($this->Entity, 50, maxLineChars: 4, fontSize: 32)->getFileContent());
        $this->assertSame('0 0 138 130', (string) $svg['viewBox']);
        $this->assertSame('138', (string) $svg->rect[0]['width']);
        $this->assertSame('130', (string) $svg->rect[0]['height']);
        $this->assertSame(2, $svg->text->count());
        $this->assertSame('32', (string) $svg->text[0]['font-size']);
        $this->assertSame('32', (string) $svg->text[1]['font-size']);
        $this->assertSame('82', (string) $svg->text[0]['y']);
        $this->assertSame('122', (string) $svg->text[1]['y']);
    }

    public function testSmallFontSizeAdjustsTheTitleLayout(): void
    {
        $this->Entity->entityData['title'] = 'WWWWWWWW';
        $svg = new SimpleXMLElement(new MakeQrSvg($this->Entity, 50, maxLineChars: 4, fontSize: 8)->getFileContent());
        $this->assertSame('0 0 50 70', (string) $svg['viewBox']);
        $this->assertSame('8', (string) $svg->text[0]['font-size']);
        $this->assertSame('58', (string) $svg->text[0]['y']);
        $this->assertSame('68', (string) $svg->text[1]['y']);
    }

    public function testNonPositiveFontSizesUseTheDefault(): void
    {
        foreach (array(0, -16) as $fontSize) {
            $svg = new SimpleXMLElement(new MakeQrSvg($this->Entity, 250, fontSize: $fontSize)->getFileContent());
            $this->assertSame('16', (string) $svg->text[0]['font-size']);
            $this->assertSame('0 0 292 270', (string) $svg['viewBox']);
        }
    }

    public function testPositiveFontSizeIsNotCappedByTheUiLimit(): void
    {
        $svg = new SimpleXMLElement(new MakeQrSvg($this->Entity, 250, fontSize: 1500)->getFileContent());
        $this->assertSame('1500', (string) $svg->text[0]['font-size']);
        $this->assertSame('2125', (string) $svg['height']);
    }

    public function testEmptyTitle(): void
    {
        $this->Entity->entityData['title'] = '';
        $svg = new SimpleXMLElement(new MakeQrSvg($this->Entity, 250)->getFileContent());
        $this->assertSame('0 0 250 250', (string) $svg['viewBox']);
        $this->assertSame(0, $svg->text->count());
    }
}
