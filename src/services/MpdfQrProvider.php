<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @author    Marcel Bolten
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Mpdf\QrCode\Output\Png;
use Mpdf\QrCode\QrCode;
use RobThree\Auth\Providers\Qr\IQRCodeProvider;
use Override;

/**
 * Implements the IQRCodeProvider necessary for Two Factor Authentication.
 * It uses mpdf/qrcode to generate the QR code locally without external dependencies.
 */
final class MpdfQrProvider implements IQRCodeProvider
{
    /**
     * Constructor
     *
     * @param array<int> $background RGB background color. Default [255, 255, 255].
     * @param array<int> $foreground RGB foreground and border color. Default [0, 0, 0].
     * @param int $compression Compression level: from 0 (default, no compression) to 9.
     */
    public function __construct(public array $background = array(255, 255, 255), public array $foreground = array(0, 0, 0), public int $compression = 0) {}

    #[Override]
    public function getMimeType(): string
    {
        return 'image/png';
    }

    /**
     * Generate the png qr code
     */
    #[Override]
    public function getQRCodeImage(string $qrText, int $size): string
    {
        $qrCode = new QrCode($qrText);
        $png = new Png();
        return $png->output($qrCode, $size, $this->background, $this->foreground, $this->compression);
    }
}
