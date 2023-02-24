<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Interfaces\StringMakerInterface;
use Elabftw\Models\AbstractEntity;
use Imagick;
use ImagickDraw;
use ImagickPixel;
use Mpdf\QrCode\Output;
use Mpdf\QrCode\QrCode;

/**
 * Make a PNG from one or several experiments or db items showing only minimal info with QR codes
 */
class MakeQrPng extends AbstractMake implements StringMakerInterface
{
    private const DEFAULT_IMAGE_SIZE_PX = 250;

    private const LINE_HEIGHT_PX = 20;

    private const SPLIT_FACTOR = 8;

    protected string $contentType = 'image/png';

    private int $fontSize = 16;

    public function __construct(
        private AbstractEntity $entity,
        private int $id,
        private int $size,
        private array $backgroundColor = array(255, 255, 255), // white
        private array $foregroundColor = array(0, 0, 0), // black
    ) {
        $this->entity->setId($this->id);
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
        $qrCode->readImageBlob($this->getQrCode());
        // Create a drawing object
        $draw = new ImagickDraw();
        $draw->setTextAlignment(Imagick::ALIGN_LEFT);
        $draw->setFont(dirname(__DIR__, 2) . '/web/assets/fonts/lato-medium-webfont.ttf');
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

        return $newImage->getImageBlob();
    }

    /**
     * @return positive-int
     */
    private function getTitleSplitSize(): int
    {
        $res = abs(intdiv($this->size, self::SPLIT_FACTOR));
        if ($res < 1) {
            return 1;
        }
        return $res;
    }

    private function getQrCode(): string
    {
        $qrCode = new QrCode($this->entity->entityData['sharelink']);
        $output = new Output\Png();
        return $output->output($qrCode, $this->size, $this->backgroundColor, $this->foregroundColor);
    }
}
