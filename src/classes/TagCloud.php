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
declare(strict_types=1);

namespace Elabftw\Elabftw;

/**
 * Generate and display a tag cloud for a given team
 */
class TagCloud
{
    /** @var Db $Db SQL Database */
    private $Db;

    /** @var int $team id of the team */
    private $team;

    /**
     * Constructor
     *
     * @param int $team
     */
    public function __construct(int $team)
    {
        $this->team = $team;
        $this->Db = Db::getConnection();
    }

    /**
     * Read all the tags from the team
     *
     * @return array
     */
    private function readAll(): array
    {
        $sql = "SELECT tag, COUNT(tag_id) AS total
            FROM tags
            LEFT JOIN tags2entity ON (tags.id = tags2entity.tag_id)
            WHERE team = :team
            GROUP BY tag ORDER BY total DESC";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->team);
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
    private function getClassFromRatio(int $ratio): string
    {
        return 'cloud-' . round($ratio, -1);
    }

    /**
     * Create an array with tag => css class for tag cloud in profile
     *
     * @return array
     */
    public function getCloudArr(): array
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
            $ratio = (int) floor((($tag['total'] - $last['total']) / $spread) * 100);
            // assign a class: font size will be different depending on ratio
            $cssClass = $this->getClassFromRatio($ratio);
            $cloudArr[$tag['tag']] = $cssClass;
        }

        return $cloudArr;
    }
}
