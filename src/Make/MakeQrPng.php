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

use function strlen;

/**
 * Make a PNG from one or several experiments or db items showing only minimal info with QR codes
 */
class MakeQrPng extends AbstractMake implements StringMakerInterface
{
    private const int DEFAULT_IMAGE_SIZE_PX = 250;

    private const int LINE_HEIGHT_PX = 20;

    private const int SPLIT_FACTOR = 8;

    protected string $contentType = 'image/png';

    private int $fontSize = 16;

    public function __construct(
        private IQRCodeProvider $qrCodeProvider,
        private AbstractEntity $entity,
        int $id,
        private int $size,
    ) {
        $this->entity->setId($id);
        // 0 means no query parameter for size
        $this->size = $this->size > 0 ? $this->size : self::DEFAULT_IMAGE_SIZE_PX;
    }

    /**
     * Get the name of the generated file
     */
    public function getFileName(): string
    {
        return sprintf(
            '%s-qr-code.elabftw.png',
            Filter::forFilesystem($this->entity->entityData['title']),
        );
    }

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

        $splitTitle = mb_str_split($this->entity->entityData['title'], $this->getTitleSplitSize());
        $fullHeight = $qrCode->getImageHeight() + (count($splitTitle) * self::LINE_HEIGHT_PX);

        // Create a new image to hold the qrcode + text
        $newImage = new Imagick();
        $newImage->newImage($qrCode->getImageWidth(), $fullHeight, new ImagickPixel('white'));
        // Copy the original image to the new image
        $newImage->compositeImage($qrCode, Imagick::COMPOSITE_OVER, 0, 0);
        // Draw the text on the new image
        foreach ($splitTitle as $key => $line) {
            $newImage->annotateImage($draw, 10, $qrCode->getImageHeight() + ($key * 20), 0, $line);
        }
        $newImage->setImageFormat('png');

        $blob = $newImage->getImageBlob();
        // use strlen for binary data, not mb_strlen
        $this->contentSize = strlen($blob);
        return $blob;
    }

    /**
     * @return positive-int
     */
    private function getTitleSplitSize(): int
    {
        $res = abs(intdiv($this->size, self::SPLIT_FACTOR));
        if ($res <= 1) {
            return 1;
        }
        // remove one to fix swallowed up characters
        return $res - 1;
    }
}
