<?php
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */
declare(strict_types=1);

namespace Elabftw\Services;

use function dirname;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Models\AbstractEntity;
use Elabftw\Traits\UploadTrait;
use function file_get_contents;
use Symfony\Component\HttpFoundation\Request;

/**
 * Mother class of the Make* services
 *
 */
abstract class AbstractMake
{
    use UploadTrait;

    public string $filePath = '';

    protected Db $Db;

    /** @var AbstractEntity $Entity */
    protected $Entity;

    public function __construct(AbstractEntity $entity)
    {
        $this->Entity = $entity;
        $this->Db = Db::getConnection();
    }

    /**
     * The filename for what we are making
     */
    abstract public function getFileName(): string;

    /**
     * Get the contents of assets/pdf.min.css
     */
    protected function getCss(): string
    {
        $css = file_get_contents(dirname(__DIR__, 2) . '/web/assets/pdf.min.css');
        if ($css === false) {
            throw new FilesystemErrorException('Cannot read the minified css file!');
        }
        return $css;
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

        return $url . '?mode=view&id=' . (string) $this->Entity->id;
    }
}
