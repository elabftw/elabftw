<?php
/**
 * \Elabftw\Elabftw\AbstractImport
 *
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * Mother class of ImportCsv and ImportZip
 */
abstract class AbstractImport
{
    /** @var \Symfony\Component\HttpFoundation\ParameterBag $Cookies cookies */
    protected $Cookies;

    /** @var Db $Db SQL Database */
    protected $Db;

    /** @var UploadedFile $UploadedFile the uploaded file */
    protected $UploadedFile;

    /** @var Users $Users instance of Users */
    protected $Users;

    /** @var int $target the item type category or userid where we do the import */
    protected $target;

    /**
     * Constructor
     *
     * @param Users $users instance of Users
     * @param Request $request instance of Request
     * @return void
     */
    public function __construct(Users $users, Request $request)
    {
        $this->Db = Db::getConnection();
        $this->Users = $users;
        $this->Cookies = $request->cookies;
        $this->target = $this->getTarget();
        $this->UploadedFile = $request->files->all()['file'];
        if ($this->UploadedFile->getError()) {
            throw new RuntimeException($this->UploadedFile->getErrorMessage());
        }

        $this->checkMimeType();
    }

    /**
     * Read the file input
     *
     * @return void
     */
    abstract protected function openFile(): void;

    /**
     * Get where we want to import the file.
     * It can be a user id for experiments or item type id for items
     *
     * @throws RuntimeException
     * @return int The type of item
     */
    private function getTarget(): int
    {
        if ($this->Cookies->get('importTarget') !== false) {
            return (int) $this->Cookies->get('importTarget');
        }
        throw new RuntimeException('No cookies found. Import aborted.');
    }

    /**
     * Look at mime type. not a trusted source, but it can prevent dumb errors
     * There is null in the mimes array because it can happen that elabftw files are like that.
     *
     * @throws RuntimeException if the mime type is not whitelisted
     * @return bool
     */
    protected function checkMimeType(): bool
    {
        $mimes = array(null, 'application/vnd.ms-excel', 'text/plain',
            'text/csv', 'text/tsv',
            'application/zip', 'application/force-download', 'application/x-zip-compressed');

        if (in_array($this->UploadedFile->getMimeType(), $mimes, true)) {
            return true;
        }
        throw new RuntimeException("This doesn't look like the right kind of file. Import aborted.");
    }
}
