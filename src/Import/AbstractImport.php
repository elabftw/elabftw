<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Import;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ImportInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Users;
use Elabftw\Services\Check;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Mother class of ImportCsv, ImportZip and ImportEln
 */
abstract class AbstractImport implements ImportInterface
{
    protected const string TAGS_SEPARATOR = '|';

    protected Db $Db;

    // final number of items imported
    protected int $inserted = 0;

    // userid for experiments, category for items, for templates we don't care about the id (0 is sent anyway)
    protected int $targetNumber = 0;

    protected AbstractEntity $Entity;

    protected array $allowedMimes = array();

    public function __construct(protected Users $Users, string $target, protected string $canread, protected string $canwrite, protected UploadedFile $UploadedFile)
    {
        $this->Db = Db::getConnection();
        // target will look like items:N or experiments:N or experiments_templates:0
        // where N is the category for items, and userid for experiments
        [$type, $id] = explode(':', $target);
        $this->targetNumber = (int) $id;
        $this->setTargetUsers($type);
        $this->Entity = EntityType::from($type)->toInstance($this->Users);
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

    protected function setTargetUsers(string $type): void
    {
        switch ($type) {
            case EntityType::Templates->value:
                // for templates we can only import for our user, so there is no target and nothing to check
            case EntityType::Items->value:
                // Note: here we don't check that the category belongs to our team as editing the request and setting an incorrect category number isn't really an issue
                return;
            case EntityType::Experiments->value:
                // check that we can import stuff in experiments of target user
                if ($this->targetNumber !== (int) $this->Users->userData['userid'] && $this->Users->isAdminOf($this->targetNumber) === false) {
                    throw new IllegalActionException('User tried to import archive in experiments of a user but they are not admin of that user');
                }
                // set the Users object to the target user
                $this->Users = new Users($this->targetNumber, $this->Users->userData['team']);
                break;
            default:
                throw new IllegalActionException('Incorrect target for import action.');
        }
    }

    /**
     * Look at MIME type. Not a trusted source, but it can prevent dumb errors.
     */
    protected function checkMimeType(): bool
    {
        if (in_array($this->UploadedFile->getMimeType(), $this->allowedMimes, true)) {
            return true;
        }
        throw new ImproperActionException("This doesn't look like the right kind of file. Import aborted.");
    }
}
