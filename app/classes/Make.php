<?php
/**
 * \Elabftw\Elabftw\Make
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

/**
 * Mother class of MakeCsv, MakePdf and MakeZip
 *
 */
abstract class Make
{
    /** instance of Experiments or Database */
    protected $Entity;

    /**
     * The filename for what we are making
     *
     * @return string
     */
    abstract protected function getCleanName();

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
        $url = $_SERVER['HTTP_REFERER'];

        if ($this->Entity->type === 'experiments') {
            $url .= 'experiments.php';
        } else {
            $url .= 'database.php';
        }

        return $url . "?mode=view&id=" . $this->Entity->id;
    }
}
