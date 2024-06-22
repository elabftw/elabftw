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

use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\MpdfProviderInterface;
use Elabftw\Models\Config;
use Elabftw\Models\Users;
use Elabftw\Traits\TwigTrait;

/**
 * Make a PDF from several experiments or db items showing only minimal info with QR codes
 */
class MakeQrPdf extends AbstractMakePdf
{
    use TwigTrait;

    public function __construct(MpdfProviderInterface $mpdfProvider, protected Users $requester, private array $entitySlugs)
    {
        parent::__construct(
            mpdfProvider: $mpdfProvider,
            includeChangelog: false
        );
    }

    /**
     * Get the name of the generated file
     */
    public function getFileName(): string
    {
        return 'qr-codes.elabftw.pdf';
    }

    public function getFileContent(): string
    {
        $renderArr = array(
            'css' => $this->getCss(),
            'entityArr' => $this->readAll(),
        );
        $Config = Config::getConfig();
        $html = $this->getTwig((bool) $Config->configArr['debug'])->render('qr-pdf.html', $renderArr);
        $this->mpdf->WriteHTML(html_entity_decode($html, ENT_HTML5, 'UTF-8'));
        $output = $this->mpdf->OutputBinaryData();
        $this->contentSize = strlen($output);
        return $output;
    }

    /**
     * Get all the entity data from the id array
     */
    private function readAll(): array
    {
        $entityArr = array();
        $siteUrl = Config::fromEnv('SITE_URL');
        foreach ($this->entitySlugs as $slug) {
            try {
                $entity = $slug->type->toInstance($this->requester, $slug->id);
                $entity->entityData['url'] = sprintf('%s/%s.php?mode=view&id=%d', $siteUrl, $entity->page, $entity->id);
                $entityArr[] = $entity;
            } catch (IllegalActionException | ResourceNotFoundException) {
                continue;
            }
        }
        return $entityArr;
    }
}
