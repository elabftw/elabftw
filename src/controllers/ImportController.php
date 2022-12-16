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
use Elabftw\Elabftw\PermissionsDefaults;
use Elabftw\Exceptions\ImproperActionException;
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
        if (str_starts_with((string) $this->request->request->get('target'), 'items')) {
            return new RedirectResponse('../../database.php?order=lastchange');
        }
        return new RedirectResponse('../../experiments.php?order=lastchange');
    }

    private function getImporter(): ImportInterface
    {
        $uploadedFile = $this->request->files->get('file');
        $allowedExtensions = array('.eln', '.zip', '.csv');

        // figure out the filetype depending on file extension
        switch ($uploadedFile->getClientOriginalExtension()) {
            case 'eln':
                return new ImportEln(
                    $this->app->Users,
                    (string) $this->request->request->get('target'),
                    (string) ($this->request->request->get('canread') ?? PermissionsDefaults::MY_TEAMS),
                    (string) ($this->request->request->get('canwrite') ?? PermissionsDefaults::USER),
                    $uploadedFile,
                    (new StorageFactory(StorageFactory::CACHE))->getStorage()->getFs(),
                );
            case 'zip':
                return new ImportZip(
                    $this->app->Users,
                    (string) $this->request->request->get('target'),
                    (string) $this->request->request->get('canread'),
                    (string) $this->request->request->get('canwrite'),
                    $uploadedFile,
                    (new StorageFactory(StorageFactory::CACHE))->getStorage()->getFs(),
                );
            case 'csv':
                return new ImportCsv(
                    $this->app->Users,
                    (string) $this->request->request->get('target'),
                    (string) $this->request->request->get('canread'),
                    (string) $this->request->request->get('canwrite'),
                    $uploadedFile,
                );
            default:
                throw new ImproperActionException(sprintf(_('Error: invalid file extension for import. Allowed extensions: %s.'), implode(', ', $allowedExtensions)));
        }
    }
}
