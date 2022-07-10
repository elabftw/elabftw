<?php declare(strict_types=1);
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\FsTools;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Factories\EntityFactory;
use Elabftw\Interfaces\ImportInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Users;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Mother class of ImportCsv, ImportZip and ImportEln
 */
abstract class AbstractImport implements ImportInterface
{
    protected const TAGS_SEPARATOR = '|';

    protected Db $Db;

    // final number of items imported
    protected int $inserted = 0;

    // userid for experiments, category for items, for templates we don't care about the id (0 is sent anyway)
    protected int $targetNumber = 0;

    protected AbstractEntity $Entity;

    // path where we extract the archive content (subfolder of cache/elab)
    protected string $tmpPath;

    // the folder name where we extract the archive
    protected string $tmpDir;

    public function __construct(protected Users $Users, protected string $target, protected string $canread, protected string $canwrite, protected UploadedFile $UploadedFile)
    {
        $this->Db = Db::getConnection();
        $entityType = $this->processTarget();
        $this->Entity = (new EntityFactory($this->Users, $entityType))->getEntity();
        // set up a temporary directory in the cache to extract the archive to
        $this->tmpDir = FsTools::getUniqueString();
        $this->tmpPath = FsTools::getCacheFolder('elab') . '/' . $this->tmpDir;
        $this->canread = Check::visibility($canread);
        $this->canwrite = Check::visibility($canwrite);
        if ($this->UploadedFile->getError()) {
            throw new ImproperActionException($this->UploadedFile->getErrorMessage());
        }

        $this->checkMimeType();
    }

    public function getInserted(): int
    {
        return $this->inserted;
    }

    protected function processTarget(): string
    {
        // we try to import a template and don't care about the rest
        if (str_starts_with($this->target, 'templates')) {
            return AbstractEntity::TYPE_TEMPLATES;
        }
        $this->targetNumber = (int) explode('_', $this->target)[1];
        if (str_starts_with($this->target, 'userid')) {
            // check that we can import stuff in experiments of target user
            if ($this->targetNumber !== (int) $this->Users->userData['userid'] && $this->Users->isAdminOf($this->targetNumber) === false) {
                throw new IllegalActionException('User tried to import archive in experiments of a user but they are not admin of that user');
            }
            // set the Users object to the target user
            $this->Users = new Users($this->targetNumber, $this->Users->userData['team']);
            return AbstractEntity::TYPE_EXPERIMENTS;
        }
        // TODO check the category is in our team
        return AbstractEntity::TYPE_ITEMS;
    }

    /**
     * Look at mime type. not a trusted source, but it can prevent dumb errors
     * There is null in the mimes array because it can happen that elabftw files are like that.
     */
    protected function checkMimeType(): bool
    {
        $mimes = array(
            null,
            'application/csv',
            'application/vnd.ms-excel',
            'text/plain',
            'text/csv',
            'text/tsv',
            'application/zip',
            'application/force-download',
            'application/x-zip-compressed',
        );

        if (in_array($this->UploadedFile->getMimeType(), $mimes, true)) {
            return true;
        }
        throw new ImproperActionException("This doesn't look like the right kind of file. Import aborted.");
    }
}
