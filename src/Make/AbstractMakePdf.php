<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Interfaces\MpdfProviderInterface;
use Elabftw\Interfaces\PdfMakerInterface;
use Elabftw\Elabftw\FsTools;
use Elabftw\Enums\Classification;
use Mpdf\Mpdf;
use Override;

/**
 * Mother class of the Make*Pdf services
 */
abstract class AbstractMakePdf extends AbstractMake implements PdfMakerInterface
{
    protected Mpdf $mpdf;

    // switch to disable notifications from within class
    // if notifications are handled by calling class
    protected bool $notifications = true;

    protected string $contentType = 'application/pdf';

    public function __construct(
        MpdfProviderInterface $mpdfProvider,
        protected bool $includeChangelog = false,
        protected Classification $classification = Classification::None,
    ) {
        parent::__construct();
        $this->mpdf = $mpdfProvider->getInstance();
    }

    #[Override]
    public function setNotifications(bool $state): void
    {
        $this->notifications = $state;
    }

    /**
     * Get the contents of assets/pdf.min.css
     */
    protected function getCss(): string
    {
        $assetsFs = FsTools::getFs(dirname(__DIR__, 2) . '/web/assets');
        return $assetsFs->read('pdf.min.css');
    }
}
