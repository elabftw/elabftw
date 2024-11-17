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
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ImportInterface;
use Elabftw\Models\ExperimentsCategories;
use Elabftw\Models\ExperimentsStatus;
use Elabftw\Models\ItemsStatus;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Import data from a file
 */
abstract class AbstractImport implements ImportInterface
{
    protected const string TAGS_SEPARATOR = '|';

    protected Db $Db;

    // final number of entries imported
    protected int $inserted = 0;

    protected Teams $Teams;

    protected array $allowedMimes = array();

    public function __construct(
        protected Users $requester,
        protected UploadedFile $UploadedFile,
    ) {
        $this->Db = Db::getConnection();
        $this->Teams = new Teams($this->requester, $this->requester->team);
        // yes, this opens it up to normal users that normally cannot create status and category,
        // but user experience takes over this consideration here
        $this->Teams->bypassWritePermission = true;
        if ($this->UploadedFile->getError()) {
            throw new ImproperActionException($this->UploadedFile->getErrorMessage());
        }
        $this->checkMimeType();
    }

    public function getInserted(): int
    {
        return $this->inserted;
    }

    protected function getStatusId(EntityType $type, string $status): int
    {
        if ($type === EntityType::Experiments || $type === EntityType::Templates) {
            $Status = new ExperimentsStatus($this->Teams);
        } else { // items or resources categories
            $Status = new ItemsStatus($this->Teams);
        }
        return $Status->getIdempotentIdFromTitle($status);
    }

    protected function getCategoryId(EntityType $type, Users $author, string $category, ?string $color = null): int
    {
        if ($type === EntityType::Experiments || $type === EntityType::Templates) {
            $Category = new ExperimentsCategories($this->Teams);
        } else { // items
            $Category = new ItemsTypes($author);
            // yes, this opens it up to normal users that normally cannot create status and category,
            // but user experience takes over this consideration here
            $Category->bypassWritePermission = true;
        }
        return $Category->getIdempotentIdFromTitle($category, $color);
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
