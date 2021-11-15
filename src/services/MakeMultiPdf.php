<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

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

    public function __construct(private MpdfProviderInterface $mpdfProvider, AbstractEntity $entity, private array $idArr)
    {
        parent::__construct($entity);
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
        $idCount = count($this->idArr);
        foreach ($this->idArr as $key => $id) {
            $this->addToPdf((int) $id);

            if ($key !== $idCount - 1) {
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
