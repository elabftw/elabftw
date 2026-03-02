<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Interfaces\StringMakerInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Services\Filter;
use Imagick;
use ImagickDraw;
use ImagickPixel;
use RobThree\Auth\Providers\Qr\IQRCodeProvider;
use Override;

use function strlen;

/**
 * Generate a PNG image with a QR Code pointing to the URL of the Entity, and optionally include the title
 */
final class MakeQrPng extends AbstractMake implements StringMakerInterface
{
    private const int DEFAULT_IMAGE_SIZE_PX = 250;

    private const int CHAR_WIDTH_PX = 8;

    private const int LINE_HEIGHT_PX = 20;

    private const int DEFAULT_MAX_LINE_CHARS = 42;

    private const int DEFAULT_MAX_LINES = 2;

    private const int SPACE_UNDER_QR = 15;

    protected string $contentType = 'image/png';

    private int $fontSize = 16;

    public function __construct(
        private IQRCodeProvider $qrCodeProvider,
        private AbstractEntity $entity,
        private int $size,
        private bool $withTitle = true,
        private int $maxLines = 0,
        private int $maxLineChars = 0,
    ) {
        // 0 means no query parameter for size
        $this->size = $this->size > 0 ? $this->size : self::DEFAULT_IMAGE_SIZE_PX;
        $this->maxLineChars = $this->maxLineChars > 0 ? $this->maxLineChars : self::DEFAULT_MAX_LINE_CHARS;
        $this->maxLines = $this->maxLines > 0 ? $this->maxLines : self::DEFAULT_MAX_LINES;
    }

    #[Override]
    public function getFileName(): string
    {
        return sprintf(
            '%s-qr-code.elabftw.png',
            Filter::forFilesystem($this->entity->entityData['title']),
        );
    }

    #[Override]
    public function getFileContent(): string
    {
        $qrCode = new Imagick();
        $qrCode->setBackgroundColor('white');
        $qrCode->readImageBlob($this->qrCodeProvider->getQRCodeImage($this->entity->entityData['sharelink'], $this->size));
        // Create a drawing object
        $draw = new ImagickDraw();
        $draw->setTextAlignment(Imagick::ALIGN_LEFT);
        $draw->setFont(dirname(__DIR__, 2) . '/vendor/mpdf/mpdf/ttfonts/Sun-ExtA.ttf');
        $draw->setFontSize($this->fontSize);


        // Create a new image to hold the qrcode + text
        $newImage = new Imagick();
        $qrCodeWidth = $qrCode->getImageWidth();

        $splitTitle = array();
        $titleWidth = 0;
        if ($this->withTitle) {
            $splitTitle = $this->splitTitle($this->entity->entityData['title']);
            $titleWidth =  mb_strlen($splitTitle[0]) * self::CHAR_WIDTH_PX;
        }

        if ($titleWidth < $qrCodeWidth) {
            $titleWidth = $qrCodeWidth;
        }
        $qrCodeWidth += $titleWidth - $qrCodeWidth;
        $height = $qrCode->getImageHeight() + (count($splitTitle) * self::LINE_HEIGHT_PX);
        $newImage->newImage($qrCodeWidth, $height, new ImagickPixel('white'));
        // Copy the original image to the new image
        $newImage->compositeImage($qrCode, Imagick::COMPOSITE_OVER, 0, 0);
        // Draw the text on the new image
        $titleMarginLeft = 10;
        if ($this->size < 100) {
            $titleMarginLeft = 5;
        }
        foreach ($splitTitle as $key => $line) {
            $newImage->annotateImage($draw, $titleMarginLeft, $qrCode->getImageHeight() + (((int) $key + 1) * self::SPACE_UNDER_QR), 0, $line);
        }
        $newImage->setImageFormat('png');

        $blob = $newImage->getImageBlob();
        // use strlen for binary data, not mb_strlen
        $this->contentSize = strlen($blob);
        return $blob;
    }

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
