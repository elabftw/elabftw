<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Interfaces\StringMakerInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Services\Filter;
use Mpdf\QrCode\Output\Svg;
use Mpdf\QrCode\QrCode;
use Override;
use RuntimeException;
use SimpleXMLElement;

use function count;
use function htmlspecialchars;
use function max;
use function mb_strlen;
use function mb_strwidth;
use function mb_substr;
use function sprintf;
use function strlen;

/**
 * Generate an SVG QR code pointing to the Entity, optionally with its title below it.
 */
final class MakeQrSvg extends AbstractMake implements StringMakerInterface
{
    private const int DEFAULT_IMAGE_SIZE_PX = 250;

    private const int CHAR_WIDTH_PX = 8;

    private const int FONT_SIZE_PX = 16;

    private const int LINE_HEIGHT_PX = 20;

    private const int DEFAULT_MAX_LINE_CHARS = 42;

    private const int DEFAULT_MAX_LINES = 2;

    private const int SPACE_UNDER_QR = 15;

    protected string $contentType = 'image/svg+xml';

    public function __construct(
        private AbstractEntity $entity,
        private int $size,
        private bool $withTitle = true,
        private int $maxLines = 0,
        private int $maxLineChars = 0,
    ) {
        $this->size = $this->size > 0 ? $this->size : self::DEFAULT_IMAGE_SIZE_PX;
        $this->maxLineChars = $this->maxLineChars > 0 ? $this->maxLineChars : self::DEFAULT_MAX_LINE_CHARS;
        $this->maxLines = $this->maxLines > 0 ? $this->maxLines : self::DEFAULT_MAX_LINES;
    }

    #[Override]
    public function getFileName(): string
    {
        return sprintf(
            '%s-qr-code.elabftw.svg',
            Filter::forFilesystem($this->entity->entityData['title']),
        );
    }

    /**
     * @psalm-suppress UnusedVariable XPath returns a live SimpleXML node.
     */
    #[Override]
    public function getFileContent(): string
    {
        $svg = new SimpleXMLElement(new Svg()->output(new QrCode($this->entity->entityData['sharelink']), $this->size));
        $splitTitle = $this->withTitle ? $this->splitTitle($this->entity->entityData['title']) : array();
        $titleMarginLeft = $this->size < 100 ? 5 : 10;
        $width = $this->size;
        foreach ($splitTitle as $line) {
            $width = max($width, mb_strwidth($line) * self::CHAR_WIDTH_PX + 2 * $titleMarginLeft);
        }
        $height = $this->size + count($splitTitle) * self::LINE_HEIGHT_PX;
        $svg['width'] = (string) $width;
        $svg['height'] = (string) $height;
        $svg['viewBox'] = sprintf('0 0 %d %d', $width, $height);
        // Extend the white background to cover the title and the wider canvas.
        $background = $svg->xpath('./*[local-name()="rect"][1]')[0] ?? throw new RuntimeException('Could not find QR SVG background.');
        $background['width'] = (string) $width;
        $background['height'] = (string) $height;

        foreach ($splitTitle as $key => $line) {
            $text = $svg->addChild('text', htmlspecialchars($line, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED, 'UTF-8')) ?? throw new RuntimeException('Could not process title line!');
            $text->addAttribute('x', (string) $titleMarginLeft);
            $text->addAttribute('y', (string) ($this->size + self::SPACE_UNDER_QR + $key * self::LINE_HEIGHT_PX));
            $text->addAttribute('font-family', 'sans-serif');
            $text->addAttribute('font-size', (string) self::FONT_SIZE_PX);
            $text->addAttribute('fill', 'black');
            // Fix the rendered width so font substitution cannot clip the title.
            $text->addAttribute('textLength', (string) (mb_strwidth($line) * self::CHAR_WIDTH_PX));
            $text->addAttribute('lengthAdjust', 'spacingAndGlyphs');
        }

        $content = $svg->asXML() ?: throw new RuntimeException('Could not generate QR SVG image.');
        if ($content === true) {
            throw new RuntimeException('Could not generate QR SVG image.');
        }
        $this->contentSize = strlen($content);
        return $content;
    }

    /**
     * @return list<string>
     */
    private function splitTitle(string $title): array
    {
        $result = array();
        $length = mb_strlen($title);
        for ($i = 0; $i < $length; $i += $this->maxLineChars) {
            $result[] = mb_substr($title, $i, $this->maxLineChars);
            if (count($result) === $this->maxLines) {
                break;
            }
        }
        return $result;
    }
}
