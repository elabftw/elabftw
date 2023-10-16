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
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\Storage;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Import\Csv;
use Elabftw\Import\Eln;
use Elabftw\Import\Zip;
use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Interfaces\ImportInterface;
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
        $this->app->Session->getFlashBag()->add('ok', $msg);
        if (str_starts_with((string) $this->request->request->get('target'), 'items')) {
            return new RedirectResponse('/database.php?order=lastchange');
        }
        return new RedirectResponse('/experiments.php?order=lastchange');
    }

    private function getImporter(): ImportInterface
    {
        $uploadedFile = $this->request->files->get('file');
        $allowedExtensions = array('.eln', '.zip', '.csv');

        // the import menu only allows basic permission to be set, so translate this in proper json
        $canread = BasePermissions::tryFrom($this->request->request->getInt('canread')) ?? BasePermissions::MyTeams;
        $canwrite = BasePermissions::tryFrom($this->request->request->getInt('canwrite')) ?? BasePermissions::User;

        // figure out the filetype depending on file extension
        switch ($uploadedFile->getClientOriginalExtension()) {
            case 'eln':
                return new Eln(
                    $this->app->Users,
                    (string) $this->request->request->get('target'),
                    $canread->toJson(),
                    $canwrite->toJson(),
                    $uploadedFile,
                    Storage::CACHE->getStorage()->getFs(),
                );
            case 'zip':
                return new Zip(
                    $this->app->Users,
                    (string) $this->request->request->get('target'),
                    $canread->toJson(),
                    $canwrite->toJson(),
                    $uploadedFile,
                    Storage::CACHE->getStorage()->getFs(),
                );
            case 'csv':
                return new Csv(
                    $this->app->Users,
                    (string) $this->request->request->get('target'),
                    $canread->toJson(),
                    $canwrite->toJson(),
                    $uploadedFile,
                );
            default:
                throw new ImproperActionException(sprintf(_('Error: invalid file extension for import. Allowed extensions: %s.'), implode(', ', $allowedExtensions)));
        }
    }
}
