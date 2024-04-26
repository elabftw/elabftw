<?php declare(strict_types=1);
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

namespace Elabftw\Make;

use Elabftw\Interfaces\MpdfProviderInterface;
use Elabftw\Interfaces\PdfMakerInterface;
use Elabftw\Models\AbstractEntity;
use Mpdf\Mpdf;

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

    public function __construct(MpdfProviderInterface $mpdfProvider, AbstractEntity $entity, protected bool $includeChangelog = false)
    {
        parent::__construct($entity);
        $this->mpdf = $mpdfProvider->getInstance();
	$this->includeChangelog = $includeChangelog;
    }

    public function setNotifications(bool $state): void
    {
        $this->notifications = $state;
    }
}
