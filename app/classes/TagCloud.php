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
 * Generate and display a tag cloud for a given user
 */
class TagCloud
{

    /** id of our user */
    private $userid;

    /** pdo object */
    private $pdo;

    /**
     * Init the object with a userid
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
     * Show the tag cloud or a message if not enough tags
     *
     * @return string $html
     */
    public function show()
    {
        $tags = $this->readAll();

        $html = "<section class='box'>";
        $html .= "<img src='img/cloud.png' alt='' class='bot5px' /> <h4 style='display:inline'>" . _('Tag cloud') . "</h4>";
        $html .= "<div class='center'>";

        // handle minimum tag count
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
            // calculate ratio
            $ratio = floor((($tag['total'] - $last['total']) / $spread) * 100);
            // assign a class: font size will be different depending on ratio
            $class = $this->getClassFromRatio($ratio);

            $html .= "<a href='experiments.php?mode=show&tag=" . $tag['tag'] . "' class='" . $class . "'>" . stripslashes($tag['tag']) . "</a> ";
        }

        $html .= "</div></section>";

        return $html;
    }
}
