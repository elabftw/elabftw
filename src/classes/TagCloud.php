<?php
/**
 * \Elabftw\Elabftw\TagCloud
 *
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use PDO;

/**
 * Generate and display a tag cloud for a given team
 */
class TagCloud
{
    private Db $Db;

    private int $team;

    public function __construct(int $team)
    {
        $this->team = $team;
        $this->Db = Db::getConnection();
    }

    /**
     * Create an array with tag => css class for tag cloud in profile
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

    /**
     * Read all the tags from the team
     */
    private function readAll(): array
    {
        $sql = 'SELECT tag, COUNT(tag_id) AS total
            FROM tags
            LEFT JOIN tags2entity ON (tags.id = tags2entity.tag_id)
            WHERE team = :team
            GROUP BY tag ORDER BY total DESC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $req->execute();

        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
    }

    /**
     * Get the CSS class for a given ratio
     * Classes are in css/tagcloud.css
     *
     * @param int $ratio between 0 and 100
     */
    private function getClassFromRatio(int $ratio): string
    {
        return 'cloud-' . (string) round($ratio, -1);
    }
}
