<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Models\AbstractEntity;

/**
 * Make a PDF from several experiments or db items
 */
class MakeMultiPdf extends AbstractMake
{
    /** @var array $idArr the input ids but in an array */
    private $idArr = array();

    /** @var \Mpdf\Mpdf $mpdf The mpdf object which contains all information for the multi entiy PDF file */
    private $mpdf;

    /**
     * Give me an id list and a type, I make multi entity PDF for you
     *
     * @param AbstractEntity $entity
     * @param string $idList 1+3+5+8
     * @return void
     */
    public function __construct(AbstractEntity $entity, string $idList)
    {
        parent::__construct($entity);

        $this->idArr = explode(' ', $idList);

        $makePdf = new MakePdf($this->Entity, true);
        $this->mpdf = $makePdf->initializeMpdf(true);
    }

    /**
     * Get the name of the generated file
     *
     * @return string
     */
    public function getFileName(): string
    {
        return 'multientries.elabftw.pdf';
    }

    /**
     * Loop over each id and add it to the PDF
     * This could be called the main function.
     *
     * @return string
     */
    public function getMultiPdf(): string
    {
        foreach ($this->idArr as $key => $id) {
            $this->addToPdf((int) $id);

            if ($key !== count($this->idArr) -1) {
                $this->mpdf->WriteHTML('<pagebreak resetpagenum="1" />');
            }
        }

        if ($this->Entity->Users->userData['pdfa']) {
            // make sure we can read the pdf in a long time
            // will embed the font and make the pdf bigger
            $this->mpdf->PDFA = true;
        }

        return $this->mpdf->Output('', 'S');
    }

    /**
     * This is where the magic happens
     *
     * @param int $id The id of the current item
     * @return void
     */
    private function addToPdf(int $id): void
    {
        $this->Entity->setId($id);
        $CurrentEntity = new MakePdf($this->Entity, true);
        $permissions = $this->Entity->getPermissions();
        if ($permissions['read']) {
            $this->mpdf->WriteHTML($CurrentEntity->getContent());
        }
    }
}
