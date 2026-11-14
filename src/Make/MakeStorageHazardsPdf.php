<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Elabftw\Env;
use Elabftw\Interfaces\MpdfProviderInterface;
use Elabftw\Models\StorageUnits;
use Elabftw\Services\Filter;
use Elabftw\Traits\TwigTrait;
use Override;

use function _;
use function dirname;
use function sprintf;
use function strlen;

/**
 * A sheet of the hazards stored in a location, for firefighters: printed and displayed on the door of the room.
 * See StorageUnits::readHazards(): it shows the hazards, never the compounds or the entries.
 */
final class MakeStorageHazardsPdf extends AbstractMakePdf
{
    use TwigTrait;

    private array $hazards;

    public function __construct(MpdfProviderInterface $mpdfProvider, private StorageUnits $storageUnits)
    {
        parent::__construct(
            mpdfProvider: $mpdfProvider,
            includeChangelog: false,
        );
        // read now: an error must be a response of its own, not a broken pdf
        $this->hazards = $this->storageUnits->readHazards();
    }

    #[Override]
    public function getFileName(): string
    {
        return sprintf('hazards-%s.pdf', Filter::forFilesystemTitle((string) $this->hazards['location']['name']));
    }

    #[Override]
    public function getFileContent(): string
    {
        $renderArr = array(
            'css' => $this->getCss(),
            'hazards' => $this->hazards,
            'url' => $this->getUrl(),
            'ghsImagesPath' => dirname(__DIR__, 2) . '/web/assets/images/ghs',
            'useCjk' => $this->storageUnits->requester->userData['cjk_fonts'],
        );
        $html = $this->getTwig(Env::asBool('DEV_MODE'))->render('storage-hazards-pdf.html', $renderArr);
        $this->mpdf->SetTitle(sprintf('%s - %s', _('Hazards'), $this->hazards['location']['full_path']));
        $this->mpdf->WriteHTML($html);
        $output = $this->mpdf->OutputBinaryData();
        $this->contentSize = strlen($output);
        return $output;
    }

    /**
     * The url of this very sheet, for the qr code in the footer: scanning it gets the up to date hazards
     */
    public function getUrl(): string
    {
        return sprintf('%s/api/v2/storage_units/%d?format=pdf', Env::asUrl('SITE_URL'), $this->hazards['location']['id']);
    }
}
