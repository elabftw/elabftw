<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Interfaces\MpdfProviderInterface;
use Elabftw\Models\Config;
use Elabftw\Models\Users;
use Elabftw\Traits\TwigTrait;
use Override;

/**
 * Make a PDF from several experiments or db items showing only minimal info with QR codes
 */
final class MakeQrPdf extends AbstractMakePdf
{
    use TwigTrait;

    public function __construct(MpdfProviderInterface $mpdfProvider, protected Users $requester, private array $entityArr)
    {
        parent::__construct(
            mpdfProvider: $mpdfProvider,
            includeChangelog: false
        );
    }

    /**
     * Get the name of the generated file
     */
    #[Override]
    public function getFileName(): string
    {
        return 'qr-codes.elabftw.pdf';
    }

    #[Override]
    public function getFileContent(): string
    {
        // add view URL to entities
        $siteUrl = Config::fromEnv('SITE_URL');
        foreach ($this->entityArr as &$entity) {
            $entity->entityData['url'] = sprintf('%s/%s?mode=view&id=%d', $siteUrl, $entity->entityType->toPage(), $entity->id);
        }
        $renderArr = array(
            'css' => $this->getCss(),
            'entityArr' => $this->entityArr,
            'useCjk' => $this->requester->userData['cjk_fonts'],
        );
        $Config = Config::getConfig();
        $html = $this->getTwig((bool) $Config->configArr['debug'])->render('qr-pdf.html', $renderArr);
        $this->mpdf->WriteHTML(html_entity_decode($html, ENT_HTML5, 'UTF-8'));
        $output = $this->mpdf->OutputBinaryData();
        $this->contentSize = strlen($output);
        return $output;
    }
}
