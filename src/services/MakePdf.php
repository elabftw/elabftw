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

use function array_push;
use DateTime;
use function dirname;
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users;
use Elabftw\Traits\TwigTrait;
use function file_get_contents;
use function is_dir;
use function mkdir;
use Mpdf\Mpdf;
use function str_replace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use function unlink;

/**
 * Create a pdf from an Entity
 */
class MakePdf extends AbstractMake
{
    use TwigTrait;

    public string $longName;

    /**
     * Constructor
     *
     * @param AbstractEntity $entity Experiments or Database
     * @param bool $temporary do we need to save it in cache folder or uploads folder
     */
    public function __construct(AbstractEntity $entity, $temporary = false)
    {
        parent::__construct($entity);

        $this->longName = $this->getLongName() . '.pdf';

        if ($temporary) {
            $this->filePath = $this->getTmpPath() . $this->getUniqueString();
        } else {
            $this->filePath = $this->getUploadsPath() . $this->longName;
            $dir = dirname($this->filePath);
            if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
                throw new FilesystemErrorException('Cannot create folder! Check permissions of uploads folder.');
            }
        }

        // suppress the "A non-numeric value encountered" error from mpdf
        // see https://github.com/baselbers/mpdf/commit
        // 5cbaff4303604247f698afc6b13a51987a58f5bc#commitcomment-23217652
        error_reporting(E_ERROR);

        // WIP
        // ToDo: implemented user setting 'append_pdfs'
        $this->Entity->Users->userData['append_pdfs'] = 1;
    }

    /**
     * Generate pdf and output it to a file
     */
    public function outputToFile(): void
    {
        $this->generate()->Output($this->filePath, 'F');

        if (!$this->Entity->Users->userData['append_pdfs']) {
            return;
        }

        $listOfPdfs = $this->getListOfPdfs();
        if (count($listOfPdfs) === 0) {
            // nothing to append
            return;
        }

        // new tmp file that also holds appended PDFs
        $outputFileName = $this->getTmpPath() . $this->getUniqueString();

        // switch for testing the different pdf tools
        // default is GhostScript
        // but can be: mupdf, pdftk, or pdftk-java
        $mergePdfs = 'pdftk-java';
        switch ($mergePdfs) {
            case 'pdftk':
                // use pdfTK to merge PDFs
                // https://www.pdflabs.com/tools/pdftk-the-pdf-toolkit/
                // might not be available on newer alpine linux versions
                // there is a port to java, see next case
                $processArray = array_merge(
                    array(
                        'pdftk',
                        $this->filePath,
                    ),
                    $listOfPdfs,
                    array(
                        'cat',
                        'output',
                        $outputFileName,
                        'dont_ask',
                    ),
                );
                break;

            case 'pdftk-java':
                // use pdfTK-java to merge PDFs
                // this way it works on alpine
                // repo https://gitlab.com/pdftk-java/pdftk
                // need to get the jar from https://gitlab.com/pdftk-java/pdftk/-/jobs/924565145/artifacts/raw/build/libs/pdftk-all.jar
                // for now the file has to be located in cache/elab
                $processArray = array_merge(
                    array(
                        'java',
                        '-jar',
                        'pdftk-all.jar',
                        $this->filePath,
                    ),
                    $listOfPdfs,
                    array(
                        'cat',
                        'output',
                        $outputFileName,
                        'dont_ask',
                    ),
                );
                break;

            case 'mupdf':
                // use muPDF's mutool to merge PDFs
                // https://mupdf.com/
                // https://pkgs.alpinelinux.org/package/edge/community/x86_64/mupdf
                $processArray = array_merge(
                    array(
                        'mutool',
                        'merge',
                        '-o',
                        $outputFileName,
                        // '-O',
                        // 'linearize',
                        $this->filePath,
                    ),
                    $listOfPdfs
                );
                break;

            default:
                // default is GhostScript to merge PDFs
                $processArray = array_merge(
                    array(
                        'gs',
                        '-dBATCH',
                        '-dNOPAUSE',
                        //'-dPDFA=1', $this->Entity->Users->userData['pdfa']
                        // https://stackoverflow.com/questions/1659147/how-to-use-ghostscript-to-convert-pdf-to-pdf-a-or-pdf-x
                        // but it will be actually a new pdf that migth not represend all aspects of the original file
                        '-sDEVICE=pdfwrite',
                        '-dAutoRotatePages=/None',
                        '-dAutoFilterColorImages=false',
                        '-dAutoFilterGrayImages=false',
                        '-dColorImageFilter=/FlateEncode',
                        '-dGrayImageFilter=/FlateEncode',
                        '-dDownsampleMonoImages=false',
                        '-dDownsampleGrayImages=false',
                        '-sOutputFile=' . $outputFileName,
                        $this->filePath,
                    ),
                    $listOfPdfs
                );
        }

        $process = new Process(
            $processArray,
            // set working directory for process
            $this->getTmpPath()
        );
        $process->run();

        // delete first tmp file
        unlink($this->filePath);
        // point to new tmp file with appended PDFs
        $this->filePath = $outputFileName;

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    /**
     * Build HTML content that will be fed to mpdf->WriteHTML()
     */
    public function getContent(): string
    {
        $Request = Request::createFromGlobals();

        if ($this->Entity->entityData['tags']) {
            $tags = '<strong>Tags:</strong> <em>' .
                str_replace('|', ' ', $this->Entity->entityData['tags']) . '</em> <br />';
        }

        $date = new DateTime($this->Entity->entityData['date'] ?? Filter::kdate());

        $locked = $this->Entity->entityData['locked'];
        $lockDate = '';
        $lockerName = '';

        if ($locked) {
            // get info about the locker
            $Locker = new Users((int) $this->Entity->entityData['lockedby']);
            $lockerName = $Locker->userData['fullname'];

            // separate the date and time
            $ldate = explode(' ', $this->Entity->entityData['lockedwhen']);
            $lockDate = $ldate[0] . ' at ' . $ldate[1];
        }

        $renderArr = array(
            'body' => $this->getBody(),
            'commentsArr' => $this->Entity->Comments->read(),
            'css' => $this->getCss(),
            'date' => $date->format('Y-m-d'),
            'elabid' => $this->Entity->entityData['elabid'],
            'fullname' => $this->Entity->entityData['fullname'],
            'includeFiles' => $this->Entity->Users->userData['inc_files_pdf'],
            'linksArr' => $this->Entity->Links->read(),
            'locked' => $locked,
            'lockDate' => $lockDate,
            'lockerName' => $lockerName,
            'pdfSig' => $Request->cookies->get('pdf_sig'),
            'stepsArr' => $this->Entity->Steps->read(),
            'tags' => $this->Entity->entityData['tags'],
            'title' => $this->Entity->entityData['title'],
            'uploadsArr' => $this->Entity->Uploads->readAll(),
            'uploadsFolder' => dirname(__DIR__, 2) . '/uploads/',
            'url' => $this->getUrl(),
            'linkBaseUrl' => Tools::getUrl($Request) . '/database.php',
            'useCjk' => $this->Entity->Users->userData['cjk_fonts'],
        );

        return $this->getTwig(new Config())->render('pdf.html', $renderArr);
    }

    /**
     * Generate pdf and return it as string
     */
    public function getPdf(): string
    {
        if (!$this->Entity->Users->userData['append_pdfs']) {
            return $this->generate()->Output('', 'S');
        }

        $this->outputToFile();
        $content = file_get_contents($this->filePath);
        unlink($this->filePath);
        if ($content === false) {
            throw new FilesystemErrorException('Could not creat merged PDF.');
        }
        return $content;
    }

    /**
     * Replace weird characters by underscores
     */
    public function getFileName(): string
    {
        $title = Filter::forFilesystem($this->Entity->entityData['title']);
        return $this->Entity->entityData['date'] . ' - ' . $title . '.pdf';
    }

    /**
     * Initialize Mpdf
     */
    public function initializeMpdf(bool $multiEntity = false): Mpdf
    {
        $format = $this->Entity->Users->userData['pdf_format'];

        // we use a custom tmp dir, not the same as Twig because its content gets deleted after pdf is generated
        $tmpDir = dirname(__DIR__, 2) . '/cache/mpdf/';
        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0700, true) && !is_dir($tmpDir)) {
            throw new FilesystemErrorException("Could not create the $tmpDir directory! Please check permissions on this folder.");
        }

        // create the pdf
        $mpdf = new Mpdf(array(
            'format' => $format,
            'tempDir' => $tmpDir,
            'mode' => 'utf-8',
        ));

        // make sure header and footer are not overlapping the body text
        $mpdf->setAutoTopMargin = 'stretch';
        $mpdf->setAutoBottomMargin = 'stretch';

        // set metadata
        $mpdf->SetAuthor($this->Entity->Users->userData['fullname']);
        $mpdf->SetTitle('eLabFTW pdf');
        $mpdf->SetSubject('eLabFTW pdf');
        $mpdf->SetCreator('www.elabftw.net');

        if (!$multiEntity) {
            $mpdf->SetAuthor($this->Entity->entityData['fullname']);
            $mpdf->SetTitle($this->Entity->entityData['title']);
            $mpdf->SetKeywords(str_replace('|', ' ', $this->Entity->entityData['tags'] ?? ''));
        }

        return $mpdf;
    }

    /**
     * Get a list of all PDFs that are attached to an entity
     *
     * @return array
     */
    private function getListOfPdfs(): array
    {
        $uploadsArr = $this->Entity->Uploads->readAll();
        $listOfPdfs = array();
        if (count($uploadsArr) > 0) {
            foreach ($uploadsArr as $upload) {
                $filePath = dirname(__DIR__, 2) . '/uploads/' . $upload['long_name'];
                if (file_exists($filePath) && preg_match('/(pdf)$/i', Tools::getExt($upload['real_name']))) {
                    array_push($listOfPdfs, $filePath);
                }
            }
        }
        return $listOfPdfs;
    }

    /**
     * Build the pdf
     */
    private function generate(): Mpdf
    {
        $mpdf = $this->initializeMpdf();

        // write content
        $mpdf->WriteHTML($this->getContent());

        if ($this->Entity->Users->userData['pdfa']) {
            // make sure we can read the pdf in a long time
            // will embed the font and make the pdf bigger
            $mpdf->PDFA = true;
        }

        return $mpdf;
    }

    /**
     * Get the contents of app/css/pdf.min.css
     */
    private function getCss(): string
    {
        $css = file_get_contents(dirname(__DIR__, 2) . '/web/app/css/pdf.min.css');
        if ($css === false) {
            throw new FilesystemErrorException('Cannot read the minified css file!');
        }
        return $css;
    }

    private function getBody(): string
    {
        $body = $this->Entity->entityData['body'];

        // convert to html if we have markdown
        if ($this->Entity->Users->userData['use_markdown']) {
            $body = Tools::md2html($body);
        }
        // we need to fix the file path in the body so it shows properly into the pdf for timestamping (issue #131)
        return str_replace('src="app/download.php?f=', 'src="' . dirname(__DIR__, 2) . '/uploads/', $body);
    }
}
