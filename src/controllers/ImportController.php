<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Controllers;

use Elabftw\Elabftw\App;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Factories\StorageFactory;
use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Interfaces\ImportInterface;
use Elabftw\Services\ImportCsv;
use Elabftw\Services\ImportEln;
use Elabftw\Services\ImportZip;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Import csv, zip or eln
 */
class ImportController implements ControllerInterface
{
    public function __construct(private App $app, private Request $request)
    {
    }

    public function getResponse(): Response
    {
        $Importer = $this->getImporter();
        $Importer->import();
        $msg = sprintf(
            '%d %s',
            $Importer->getInserted(),
            ngettext('item imported successfully.', 'items imported successfully.', $Importer->getInserted()),
        );
        // @phpstan-ignore-next-line
        $this->app->Session->getFlashBag()->add('ok', $msg);
        // TODO redirect to correct page
        return new RedirectResponse('../../database.php');
    }

    private function getImporter(): ImportInterface
    {
        $uploadedFile = $this->request->files->get('file');

        switch ($this->request->request->get('type')) {
            case 'csv':
                return new ImportCsv(
                    $this->app->Users,
                    (int) $this->request->request->get('target'),
                    (string) $this->request->request->get('delimiter'),
                    $this->request->request->getAlnum('canread'),
                    $this->request->request->getAlnum('canwrite'),
                    $uploadedFile,
                );
            case 'archive':
                // figure out the filetype depending on file extension
                if ($uploadedFile->getClientOriginalExtension() === 'zip') {
                    return new ImportZip(
                        $this->app->Users,
                        (int) $this->request->request->get('target'),
                        $this->request->request->getAlnum('canread'),
                        $this->request->request->getAlnum('canwrite'),
                        $uploadedFile,
                        (new StorageFactory(StorageFactory::CACHE))->getStorage()->getFs(),
                    );
                } elseif ($uploadedFile->getClientOriginalExtension() === 'eln') {
                    return new ImportEln(
                        $this->app->Users,
                        (string) $this->request->request->get('target'),
                        $this->request->request->getAlnum('canread'),
                        $this->request->request->getAlnum('canwrite'),
                        $uploadedFile,
                        (new StorageFactory(StorageFactory::CACHE))->getStorage()->getFs(),
                    );
                }
                // no break
            default:
                throw new IllegalActionException('Invalid file type for import');
        }
    }
}
