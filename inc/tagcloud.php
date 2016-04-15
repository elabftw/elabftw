<?php
/**
 * inc/tagcloud.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 */

echo "<section class='box'>";
echo "<img src='img/cloud.png' alt='' class='bot5px' /> <h4 style='display:inline'>" . _('Tag cloud') . "</h4>";
echo "<div class='center'>";
// 1. Create an array with tag -> count
$sql = "SELECT tag, COUNT(*) AS total
    FROM experiments_tags
    WHERE userid = :userid
    GROUP BY tag ORDER BY total DESC";
$req = $pdo->prepare($sql);
$req->bindParam(':userid', $_SESSION['userid'], PDO::PARAM_INT);
$req->execute();
$full = $req->fetchAll();

// need at least 10 tags to make a cloud
if (count($full) > 10) {

    // calculate the spread, max number of tag occurence - min number of tag occurence
    $first = reset($full);
    $last = end($full);
    $spread = $first['total'] - $last['total'];
    if ($spread === 0) {
        $spread = 1;
    }

    // randomize the tags
    shuffle($full);

    foreach ($full as $tag) {
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

        echo "<a href='experiments.php?mode=show&tag=" . $tag['tag'] . "' class='" . $class . "'>" . stripslashes($tag['tag']) . "</a> ";
    }
    // TAGCLOUD
    echo "</div>";
} else {
    echo _('Not enough tags to make a tagcloud.');
}
?>
</section>
