<?php
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */
declare(strict_types=1);

namespace Elabftw\Services;

use Mpdf\QrCode\QrCode;
use Mpdf\QrCode\Output\Png;
use RobThree\Auth\Providers\Qr\IQRCodeProvider;

/**
 * Implements the IQRCodeProvider necessary for Two Factor Authentication.
 * It uses mpdf/qrcode to generate the QR code locally without external dependencies. 
 */
class MpdfQrProvider implements IQRCodeProvider
{
    /** @var array $background */
    public $background = array();

    /** @var array $color */
    public $color = array();

    /** @var array $compression */
    public $compression;

    /**
     * Constructor
     *
     * @param array $background RGB background color. Default [255, 255, 255].
     * @param array $color RGB foreground and border color. Default [0, 0, 0].
     * @param int $compression Compression level: from 0 (default, no compression) to 9.
     */
    public function __construct(array $background = [255, 255, 255], array $color = [0, 0, 0], int $compression = 0)
    {
        $this->background = $background;
        $this->color = $color;
        $this->compression = $compression;
    }

    /**
     * getMimeType
     *
     * @return string The mime type
     */
    public function getMimeType() : string
    {
        return 'image/png';
    }

    /**
     * getQRCodeImage
     *
     * @param string $qrtext
     * @param int $size
     *
     * @return string 
     */
    public function getQRCodeImage(string $qrtext, int $size) : string
    {
        $qrCode = new QrCode($qrtext);
        $png = new Png();
        $result = $png->output($qrCode, $size, $this->background, $this->color, $this->compression);

        return $result;
    }
}
