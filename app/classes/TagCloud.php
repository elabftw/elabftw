<?php
/**
 * \Elabftw\Elabftw\TagCloud
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;


/**
 * Generate and display a tag cloud for a given user
 */
class TagCloud
{
    /** @var Db $Db SQL Database */
    private $Db;

    /** @var string $userid id of our user */
    private $userid;

    /**
     * Init the object with a userid
     *
     * @param string $userid
     */
    public function __construct($userid)
    {
        $this->userid = $userid;
        $this->Db = Db::getConnection();
    }

    /**
     * Read all the tags from user
     *
     * @return array
     */
    private function readAll()
    {
        $sql = "SELECT tag, COUNT(*) AS total
            FROM experiments_tags
            WHERE userid = :userid
            GROUP BY tag ORDER BY total DESC";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Get the CSS class for a given ratio
     * Classes are in css/tagcloud.css
     *
     * @param int $ratio between 0 and 100
     * @return string
     */
    private function getClassFromRatio($ratio)
    {
        return 'cloud-' . round($ratio, -1);
    }

    /**
     * Create an array with tag => css class for tag cloud in profile
     *
     * @return array
     */
    public function getCloudArr()
    {
        $tags = $this->readAll();
        $first = reset($tags);
        $last = end($tags);
        $spread = $first['total'] - $last['total'];

        if ($spread === 0) {
            $spread = 1;
        }

        // randomize the tags
        shuffle($tags);

        // fill the array
        $cloudArr = array();
        foreach ($tags as $tag) {
            // calculate ratio
            $ratio = floor((($tag['total'] - $last['total']) / $spread) * 100);
            // assign a class: font size will be different depending on ratio
            $cssClass = $this->getClassFromRatio($ratio);
            $cloudArr[$tag['tag']] = $cssClass;
        }

        return $cloudArr;
    }
}
