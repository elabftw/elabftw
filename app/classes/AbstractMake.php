<?php
/**
 * \Elabftw\Elabftw\AbstractMake
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Symfony\Component\HttpFoundation\Request;

/**
 * Mother class of MakeCsv, MakePdf and MakeZip
 *
 */
abstract class AbstractMake
{
    /** @var AbstractEntity $Entity instance of Experiments or Database */
    protected $Entity;

    /** @var Db $Db SQL Database */
    protected $Db;

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
    abstract public function getCleanName();

    /**
     * Generate a long and unique string
     *
     * @return string a sha512 hash of uniqid()
     */
    protected function getUniqueString()
    {
        return hash("sha512", uniqid(rand(), true));
    }

    /**
     * Attach the absolute path to a filename
     *
     * @param string $fileName
     * @param bool $tmp set to true if you want a temporary path (in uploads/tmp)
     * @return string Absolute path
     */
    protected function getFilePath($fileName, $tmp = false)
    {
        $tempPath = '';

        if ($tmp) {
            $tempPath = 'tmp/';
        }
        return ELAB_ROOT . 'uploads/' . $tempPath . $fileName;
    }

    /**
     * Return the url of the item or experiment
     *
     * @return string url to the item/experiment
     */
    protected function getUrl()
    {
        $Request = Request::createFromGlobals();
        $url = 'https://' . $Request->getHttpHost() . '/' . $this->Entity->page . '.php';

        return $url . "?mode=view&id=" . $this->Entity->id;
    }
}
