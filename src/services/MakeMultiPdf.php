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

use Elabftw\Interfaces\FileMakerInterface;
use Elabftw\Interfaces\MpdfProviderInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Traits\PdfTrait;

/**
 * Make a PDF from several experiments or db items
 */
class MakeMultiPdf extends AbstractMake implements FileMakerInterface
{
    use PdfTrait;

    // the input ids but in an array
    private array $idArr = array();

    /**
     * Give me an id list and a type, I make multi entity PDF for you
     *
     * @param string $idList 4 8 15 16 23 42
     */
    public function __construct(private MpdfProviderInterface $mpdfProvider, AbstractEntity $entity, string $idList)
    {
        parent::__construct($entity);

        $this->idArr = explode(' ', $idList);

        $this->mpdf = $mpdfProvider->getInstance();
    }

    /**
     * Get the name of the generated file
     */
    public function getFileName(): string
    {
        return 'multientries.elabftw.pdf';
    }

    /**
     * Loop over each id and add it to the PDF
     * This could be called the main function.
     */
    public function getFileContent(): string
    {
        foreach ($this->idArr as $key => $id) {
            $this->addToPdf((int) $id);

            if ($key !== count($this->idArr) -1) {
                $this->mpdf->AddPageByArray(array(
                    'sheet-size' => $this->Entity->Users->userData['pdf_format'],
                    'resetpagenum' => 1,
                ));
            }
        }

        return $this->mpdf->Output('', 'S');
    }

    /**
     * This is where the magic happens
     *
     * @param int $id The id of the current item
     */
    private function addToPdf(int $id): void
    {
        $this->Entity->setId($id);
        $permissions = $this->Entity->getPermissions();
        $CurrentEntity = new MakePdf($this->mpdfProvider, $this->Entity, true);
        if ($permissions['read']) {
            // write content
            // FIXME: in multi mode, the attached files are currently not appended
            $this->mpdf->WriteHTML($CurrentEntity->getContent());
        }
    }
}
