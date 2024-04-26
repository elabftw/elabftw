<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Controllers;

use Elabftw\AuditEvent\Import;
use Elabftw\Elabftw\App;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\Storage;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Import\Csv;
use Elabftw\Import\Eln;
use Elabftw\Import\Zip;
use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Interfaces\ImportInterface;
use Elabftw\Models\AuditLogs;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Import csv, zip or eln
 */
class ImportController implements ControllerInterface
{
    private const int AUDIT_THRESHOLD = 12;

    public function __construct(private App $App) {}

    public function getResponse(): Response
    {
        $Importer = $this->getImporter();
        $Importer->import();
        $inserted = $Importer->getInserted();
        $msg = sprintf(
            '%d %s',
            $inserted,
            ngettext('item imported successfully.', 'items imported successfully.', $inserted),
        );
        if ($inserted > self::AUDIT_THRESHOLD) {
            AuditLogs::create(new Import($this->App->Users->userid ?? 0, $inserted));
        }
        $this->App->Session->getFlashBag()->add('ok', $msg);
        if (str_starts_with($this->App->Request->request->getString('target'), 'items')) {
            return new RedirectResponse('/database.php?order=lastchange');
        }
        return new RedirectResponse('/experiments.php?order=lastchange');
    }

    private function getImporter(): ImportInterface
    {
        $uploadedFile = $this->App->Request->files->get('file');
        $allowedExtensions = array('.eln', '.zip', '.csv');

        // the import menu only allows basic permission to be set, so translate this in proper json
        $canread = BasePermissions::tryFrom($this->App->Request->request->getInt('canread')) ?? BasePermissions::Team;
        $canwrite = BasePermissions::tryFrom($this->App->Request->request->getInt('canwrite')) ?? BasePermissions::User;

        // figure out the filetype depending on file extension
        switch ($uploadedFile->getClientOriginalExtension()) {
            case 'eln':
                return new Eln(
                    $this->App->Users,
                    $this->App->Request->request->getString('target'),
                    $canread->toJson(),
                    $canwrite->toJson(),
                    $uploadedFile,
                    Storage::CACHE->getStorage()->getFs(),
                );
            case 'zip':
                return new Zip(
                    $this->App->Users,
                    $this->App->Request->request->getString('target'),
                    $canread->toJson(),
                    $canwrite->toJson(),
                    $uploadedFile,
                    Storage::CACHE->getStorage()->getFs(),
                );
            case 'csv':
                return new Csv(
                    $this->App->Users,
                    $this->App->Request->request->getString('target'),
                    $canread->toJson(),
                    $canwrite->toJson(),
                    $uploadedFile,
                );
            default:
                throw new ImproperActionException(sprintf(_('Error: invalid file extension for import. Allowed extensions: %s.'), implode(', ', $allowedExtensions)));
        }
    }
}
