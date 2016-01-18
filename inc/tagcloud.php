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
$count = count($full);
// need at least 10 tags to make a cloud
if ($count > 10) {
    // max occurence = first result in array
    $maxoccur = $full[0][1];
    // min occurenc = last result in array
    $minoccur = $full[$count - 1][1];

    // 2nd SQL to get the tags unsorted
    $sql = "SELECT tag, COUNT(*) AS total FROM experiments_tags WHERE userid = :userid GROUP BY tag";
    $req = $pdo->prepare($sql);
    $req->bindParam(':userid', $_SESSION['userid'], PDO::PARAM_INT);
    $req->execute();
    $spread = $maxoccur - $minoccur;
    if ($spread === 0) {
        $spread = 1;
    }
    while ($data = $req->fetch()) {
        // Calculate ratio
        $ratio = floor((($data[1] - $minoccur) / $spread) * 100);
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
        echo "<a href='experiments.php?mode=show&q=" . $data[0] . "' class='" . $class . "'>" . stripslashes($data[0]) . "</a> ";
    }
    // TAGCLOUD
    echo "</div>";
} else {
    echo _('Not enough tags to make a tagcloud.');
}// end fix division by zero
?>
</section>
