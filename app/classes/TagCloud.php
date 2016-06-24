<?php
/**
 * \Elabftw\Elabftw\TagCloud
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

/**
 * Show tag cloud
 */
class TagCloud
{
    /**
     * Need userid
     *
     * @param string $userid
     */
    public function __construct($userid)
    {
        $this->userid = $userid;
        $this->pdo = Db::getConnection();
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
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':userid', $this->userid);
        $req->execute();

        return $req->fetchAll();
    }

    public function show()
    {
        $tags = $this->readAll();

        $html = "<section class='box'>";
        $html .= "<img src='img/cloud.png' alt='' class='bot5px' /> <h4 style='display:inline'>" . _('Tag cloud') . "</h4>";
        $html .= "<div class='center'>";

        if (count($tags) <= 10) {
            $html .= _('Not enough tags to make a tagcloud.');
            $html .= "</div></section>";
            return $html;
        }

        // calculate the spread, max number of tag occurence - min number of tag occurence
        $first = reset($tags);
        $last = end($tags);
        $spread = $first['total'] - $last['total'];
        if ($spread === 0) {
            $spread = 1;
        }

        // randomize the tags
        shuffle($tags);

        foreach ($tags as $tag) {
            // Calculate ratio
            $ratio = floor((($tag['total'] - $last['total']) / $spread) * 100);

            if ($ratio < 10) {
                $class = 'c1';
            } elseif ($ratio >= 10 && $ratio < 20) {
                $class = 'c2';
            } elseif ($ratio >= 20 && $ratio < 30) {
                $class = 'c3';
            } elseif ($ratio >= 30 && $ratio < 40) {
                $class = 'c4';
            } elseif ($ratio >= 40 && $ratio < 50) {
                $class = 'c5';
            } elseif ($ratio >= 50 && $ratio < 60) {
                $class = 'c6';
            } elseif ($ratio >= 60 && $ratio < 70) {
                $class = 'c7';
            } elseif ($ratio >= 70 && $ratio < 80) {
                $class = 'c8';
            } elseif ($ratio >= 80 && $ratio < 90) {
                $class = 'c9';
            } else {
                $class = 'c10';
            }

            $html .= "<a href='experiments.php?mode=show&tag=" . $tag['tag'] . "' class='" . $class . "'>" . stripslashes($tag['tag']) . "</a> ";
        }
        // TAGCLOUD
        $html .= "</div></section>";

        return $html;
    }
}
