<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\CreateNotificationParams;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Interfaces\FileMakerInterface;
use Elabftw\Interfaces\MpdfProviderInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Notifications;
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
        if ($this->errors) {
            $Notifications = new Notifications($this->Entity->Users);
            $Notifications->create(new CreateNotificationParams(Notifications::PDF_GENERIC_ERROR));
        }

        return $this->mpdf->Output('', 'S');
    }

    /**
     * This is where the magic happens
     */
    private function addToPdf(int $id): void
    {
        $this->Entity->setId($id);
        try {
            $permissions = $this->Entity->getPermissions();
        } catch (IllegalActionException $e) {
            return;
        }
        if ($permissions['read']) {
            $currentEntity = new MakePdf($this->mpdfProvider, $this->Entity);
            $currentEntity->createNotifications = false;
            // write content
            $this->mpdf->WriteHTML($currentEntity->getContent());

            // attached files are appended based on user setting
            if ($this->Entity->Users->userData['append_pdfs']) {
                $currentEntity->appendPdfs($currentEntity->getAttachedPdfs(), $this->mpdf);
                if ($currentEntity->failedAppendPdfs) {
                    $currentEntity->errors[] = array(
                        'type' => Notifications::PDF_APPENDMENT_FAILED,
                        'body' => array(
                            'entity_id' => $currentEntity->Entity->id,
                            'entity_page' => $currentEntity->Entity->page,
                            'file_names' => implode(', ', $currentEntity->failedAppendPdfs),
                        ),
                    );
                }
            }
            array_push($this->errors, ...$currentEntity->errors);
        }
    }
}
