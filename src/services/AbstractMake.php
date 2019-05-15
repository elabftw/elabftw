<?php
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */
declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Models\AbstractEntity;
use Elabftw\Traits\UploadTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Mother class of the Make* services
 *
 */
abstract class AbstractMake
{
    use UploadTrait;

    /** @var AbstractEntity $Entity instance of Experiments or Database */
    protected $Entity;

    /** @var Db $Db SQL Database */
    protected $Db;

    /** @var string $filePath the full path of the file */
    public $filePath;

    /**
     * Constructor
     *
     * @param AbstractEntity $entity
     */
    public function __construct(AbstractEntity $entity)
    {
        $this->Entity = $entity;
        $this->Db = Db::getConnection();
    }


    /**
     * The filename for what we are making
     *
     * @return string
     */
    abstract public function getFileName(): string;

    /**
     * Create a unique long filename with a folder
     *
     * @return string the path for storing the file
     */
    protected function getLongName(): string
    {
        $hash = \hash("sha512", \bin2hex(\random_bytes(16)));
        $folder = substr($hash, 0, 2);

        return $folder . '/' . $hash;
    }

    /**
     * Get the temporary files folder absolute path
     * Create the folder if it doesn't exist
     *
     * @return string absolute path
     */
    protected function getTmpPath(): string
    {
        $tmpPath = \dirname(__DIR__, 2) . '/cache/elab/';
        if (!is_dir($tmpPath) && !mkdir($tmpPath, 0700, true) && !is_dir($tmpPath)) {
            throw new FilesystemErrorException("Unable to create the cache directory ($tmpPath)");
        }

        return $tmpPath;
    }

    /**
     * Return the url of the item or experiment
     *
     * @return string url to the item/experiment
     */
    protected function getUrl(): string
    {
        $Request = Request::createFromGlobals();
        $url = Tools::getUrl($Request) . '/' . $this->Entity->page . '.php';

        return $url . "?mode=view&id=" . $this->Entity->id;
    }
}
