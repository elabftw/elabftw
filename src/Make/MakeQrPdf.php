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

use Elabftw\Elabftw\DisplayParams;
use Elabftw\Elabftw\Tools;
use Elabftw\Interfaces\MpdfProviderInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Config;
use Elabftw\Traits\TwigTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Make a PDF from several experiments or db items showing only minimal info with QR codes
 */
class MakeQrPdf extends AbstractMakePdf
{
    use TwigTrait;

    public function __construct(MpdfProviderInterface $mpdfProvider, AbstractEntity $entity, private array $idArr)
    {
        parent::__construct(
            mpdfProvider: $mpdfProvider,
            entity: $entity,
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
            'useCjk' => $this->Entity->Users->userData['cjk_fonts'],
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
        $DisplayParams = new DisplayParams($this->Entity->Users, Request::createFromGlobals(), $this->Entity->entityType);
        $DisplayParams->limit = 9001;
        $this->Entity->idFilter = Tools::getIdFilterSql($this->idArr);
        $entityArr = $this->Entity->readShow($DisplayParams, true);
        foreach ($entityArr as &$entity) {
            $entity['url'] = $this->getUrl((int) $entity['id']);
        }
        return $entityArr;
    }
}
